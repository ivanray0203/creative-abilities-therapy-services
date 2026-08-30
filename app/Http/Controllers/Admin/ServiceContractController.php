<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceContractRequest;
use App\Http\Requests\Admin\UpdateServiceContractRequest;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\ServiceContract;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Phase 20 — admin's authorization of an availed service.
 *
 * Contracts hang off the client's Services tab rather than getting a page of
 * their own: an admin issues one while looking at the service it covers, and
 * that is the only place the numbers make sense.
 *
 * There is no index here for the same reason. `Client::refreshServiceAvailedCache()`
 * carries each service's live contract summary onto the client page.
 */
class ServiceContractController extends Controller
{
    public function __construct(private ReferenceNumberGenerator $referenceNumbers) {}

    public function store(StoreServiceContractRequest $request, Client $client, ClientService $clientService): RedirectResponse
    {
        $this->guardServiceBelongsToClient($client, $clientService);

        /*
         * The funding code is not defaulted here. The form arrives with the
         * funder already on record pre-selected — see
         * Admin\ClientController::attachContractBalances() — so what an admin
         * submits is what they saw and agreed to, and "Not set" stays a
         * choice they can actually make.
         */
        $contract = $clientService->contracts()->create([
            ...$request->validated(),
            'contract_number' => $this->referenceNumbers->serviceContract(),
            // Snapshot: the availed service's therapist can be changed later,
            // and authorized hours must not follow that move on their own.
            'therapist_id' => $clientService->therapist_id,
            'issued_by_id' => $request->user()->id,
            'status' => ServiceContract::STATUS_ACTIVE,
        ]);

        $client->refreshServiceAvailedCache();

        AuditLogger::log(
            'Issued service contract',
            'Clients',
            "Issued contract {$contract->contract_number} for {$contract->allotted_hours} hours on service #{$clientService->id}",
        );

        return back()->with('success', "Contract {$contract->contract_number} issued.");
    }

    public function update(
        UpdateServiceContractRequest $request,
        Client $client,
        ClientService $clientService,
        ServiceContract $contract,
    ): RedirectResponse {
        $this->guardServiceBelongsToClient($client, $clientService);
        $this->guardContractBelongsToService($clientService, $contract);

        $contract->update($request->validated());

        // An edit can put a contract back in range, or take it out of one.
        $contract->update(['status' => $contract->derivedStatus()]);

        $client->refreshServiceAvailedCache();

        AuditLogger::log('Updated service contract', 'Clients', "Updated contract {$contract->contract_number}");

        return back()->with('success', "Contract {$contract->contract_number} updated.");
    }

    /**
     * Withdraw a contract without erasing what was delivered under it.
     *
     * This is the way out for a contract that has sessions against it —
     * `destroy()` refuses those, because deleting one would silently detach
     * the hours from every session that drew them.
     */
    public function cancel(Request $request, Client $client, ClientService $clientService, ServiceContract $contract): RedirectResponse
    {
        $this->guardServiceBelongsToClient($client, $clientService);
        $this->guardContractBelongsToService($clientService, $contract);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $contract->update([
            'status' => ServiceContract::STATUS_CANCELLED,
            'notes' => $validated['notes'] ?? $contract->notes,
        ]);

        $client->refreshServiceAvailedCache();

        AuditLogger::log(
            'Cancelled service contract',
            'Clients',
            "Cancelled contract {$contract->contract_number}",
            'warning',
        );

        return back()->with('success', "Contract {$contract->contract_number} cancelled.");
    }

    public function destroy(Client $client, ClientService $clientService, ServiceContract $contract): RedirectResponse
    {
        $this->guardServiceBelongsToClient($client, $clientService);
        $this->guardContractBelongsToService($clientService, $contract);

        if ($contract->sessions()->exists()) {
            return back()->with(
                'error',
                'Sessions have already drawn hours from this contract, so it can no longer be deleted. Cancel it instead.',
            );
        }

        $number = $contract->contract_number;
        $contract->delete();

        $client->refreshServiceAvailedCache();

        AuditLogger::log('Deleted service contract', 'Clients', "Deleted contract {$number}", 'warning');

        return back()->with('success', "Contract {$number} deleted.");
    }

    private function guardServiceBelongsToClient(Client $client, ClientService $clientService): void
    {
        if ($clientService->client_id !== $client->id) {
            abort(404);
        }
    }

    private function guardContractBelongsToService(ClientService $clientService, ServiceContract $contract): void
    {
        if ($contract->client_service_id !== $clientService->id) {
            abort(404);
        }
    }
}

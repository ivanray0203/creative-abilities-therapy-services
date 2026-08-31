<?php

namespace App\Http\Requests\Admin;

/**
 * Same field set as issuing a contract. The two rules that only bite on an
 * edit — no overlap with the *other* contracts, and no cutting the allotment
 * below what has already been drawn — read the bound contract from the route,
 * so the parent covers both cases without a second rule set.
 */
class UpdateServiceContractRequest extends StoreServiceContractRequest {}

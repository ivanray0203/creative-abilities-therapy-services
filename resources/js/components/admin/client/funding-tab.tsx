import { CalendarDays, DollarSign } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import type { Client } from '@/types/client';

/** Reference: cats-frontend/src/pages/admin/clientTabs/Funding.tsx */
export default function FundingTab({ client }: { client: Client }) {
    const intake = client.original_intake;
    const funding = intake?.funding_source_info ?? {};
    const isInsurance = intake?.funding_source === 'Insurance';
    const isPrivate = intake?.funding_source === 'private';
    const isFscd = !isInsurance && !isPrivate;

    return (
        <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="flex flex-row items-center gap-3">
                        <DollarSign className="text-primary" /> Funding Details
                    </p>

                    <div className="mt-10 grid grid-cols-2 gap-5 md:grid-cols-4">
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Funding Source
                            </p>
                            <p>{intake?.funding_source || '-'}</p>
                        </div>

                        {isFscd && (
                            <>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        FSCD Worker Name
                                    </p>
                                    <p>
                                        {funding.FSCD_case_worker_name || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        FSCD Approval Start
                                    </p>
                                    <p>
                                        {funding.FSCD_approval_start_date ||
                                            '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        FSCD Approval End
                                    </p>
                                    <p>
                                        {funding.FSCD_approval_end_date || '-'}
                                    </p>
                                </div>
                            </>
                        )}

                        {isInsurance && (
                            <>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Insurance Provider
                                    </p>
                                    <p>{funding.insurance_provider || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Policy Number
                                    </p>
                                    <p>{funding.policy_number || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Certificate Number
                                    </p>
                                    <p>{funding.certificate_number || '-'}</p>
                                </div>
                            </>
                        )}
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="flex flex-row items-center gap-3">
                        <CalendarDays className="text-primary" /> Contract Dates
                    </p>

                    <div className="mt-10 grid grid-cols-1 gap-5 md:grid-cols-3">
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Contract Start Date
                            </p>
                            <p>{client.contract_start_date || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Contract End Date
                            </p>
                            <p>{client.contract_end_date || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Signed Date
                            </p>
                            <p>{client.signed_date || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="p-5 text-center text-muted-foreground">
                    Funding History — Coming Soon
                </CardContent>
            </Card>
        </div>
    );
}

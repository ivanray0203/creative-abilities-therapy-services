import { useForm } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type { EmploymentStatus, TeamMember } from '@/types/team-member';

const EMPLOYMENT_STATUSES: EmploymentStatus[] = [
    'onboarding',
    'active',
    'inactive',
    'on_leave',
    'terminated',
    'archived',
];

interface AccessForm {
    can_access_finance: boolean;
    can_manage_team: boolean;
    can_manage_clients: boolean;
    employment_status: EmploymentStatus;
}

/** Reference: cats-frontend/src/modals/ManageTeamAccesModal.tsx */
export default function ManageTeamAccessModal({
    teamMember,
    isOpen,
    onClose,
}: {
    teamMember: TeamMember;
    isOpen: boolean;
    onClose: () => void;
}) {
    const { data, setData, patch, processing } = useForm<AccessForm>({
        can_access_finance: teamMember.can_access_finance,
        can_manage_team: teamMember.can_manage_team,
        can_manage_clients: teamMember.can_manage_clients,
        employment_status: teamMember.employment_status,
    });

    const submit = () => {
        patch(`/admin/team/${teamMember.id}/access`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="w-full max-w-md p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        Manage Access and Status
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-5">
                    <div className="flex items-center justify-between">
                        <Label>Finance Access</Label>
                        <Switch
                            checked={data.can_access_finance}
                            onCheckedChange={(checked) =>
                                setData('can_access_finance', checked)
                            }
                        />
                    </div>
                    <div className="flex items-center justify-between">
                        <Label>Team Management</Label>
                        <Switch
                            checked={data.can_manage_team}
                            onCheckedChange={(checked) =>
                                setData('can_manage_team', checked)
                            }
                        />
                    </div>
                    <div className="flex items-center justify-between">
                        <Label>Client Management</Label>
                        <Switch
                            checked={data.can_manage_clients}
                            onCheckedChange={(checked) =>
                                setData('can_manage_clients', checked)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="access-employment-status">
                            Employment Status
                        </Label>
                        <Select
                            value={data.employment_status}
                            onValueChange={(value) =>
                                setData(
                                    'employment_status',
                                    value as EmploymentStatus,
                                )
                            }
                        >
                            <SelectTrigger
                                id="access-employment-status"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {EMPLOYMENT_STATUSES.map((status) => (
                                    <SelectItem key={status} value={status}>
                                        {status}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <DialogFooter className="mt-6">
                    <Button
                        variant="outline"
                        className="rounded-[10px]"
                        onClick={onClose}
                    >
                        Cancel
                    </Button>
                    <Button
                        className="rounded-[10px]"
                        onClick={submit}
                        disabled={processing}
                    >
                        Update
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

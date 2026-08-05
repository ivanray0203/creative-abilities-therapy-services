import { useForm } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AdminUser } from '@/types/administrator';

interface AdminUserForm {
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
}

function initialValues(adminUser?: AdminUser | null): AdminUserForm {
    return {
        first_name: adminUser?.first_name ?? '',
        last_name: adminUser?.last_name ?? '',
        email: adminUser?.email ?? '',
        phone: adminUser?.phone ?? '',
    };
}

/** Reference: cats-frontend/src/modals/AddAdminUserModal.tsx */
export default function AddAdminUserModal({
    adminUser,
    isOpen,
    onClose,
}: {
    adminUser?: AdminUser | null;
    isOpen: boolean;
    onClose: () => void;
}) {
    const isEdit = adminUser != null;

    const {
        data,
        setData,
        post,
        patch,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm<AdminUserForm>(initialValues(adminUser));

    const closeAndReset = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = () => {
        if (isEdit && adminUser) {
            patch(`/admin/users/${adminUser.id}/admin-update`, {
                preserveScroll: true,
                onSuccess: closeAndReset,
            });

            return;
        }

        post('/admin/administrator/users', {
            preserveScroll: true,
            onSuccess: closeAndReset,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && closeAndReset()}>
            <DialogContent className="w-full max-w-md p-6">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        {isEdit ? 'Edit Admin User' : 'Add Admin User'}
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-3">
                    <div>
                        <Label htmlFor="admin-first-name">First Name *</Label>
                        <Input
                            id="admin-first-name"
                            value={data.first_name}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('first_name', event.target.value)
                            }
                        />
                        {errors.first_name && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.first_name}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="admin-last-name">Last Name *</Label>
                        <Input
                            id="admin-last-name"
                            value={data.last_name}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('last_name', event.target.value)
                            }
                        />
                        {errors.last_name && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.last_name}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="admin-email">Email *</Label>
                        <Input
                            id="admin-email"
                            type="email"
                            value={data.email}
                            disabled={isEdit}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                        />
                        {errors.email && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.email}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="admin-phone">Phone</Label>
                        <Input
                            id="admin-phone"
                            value={data.phone}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('phone', event.target.value)
                            }
                        />
                    </div>
                </div>

                <DialogFooter className="mt-6">
                    <Button
                        variant="outline"
                        className="rounded-[10px]"
                        onClick={closeAndReset}
                    >
                        Cancel
                    </Button>
                    <Button
                        className="rounded-[10px]"
                        onClick={submit}
                        disabled={processing}
                    >
                        {isEdit ? 'Save Changes' : 'Add User'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

import { router } from '@inertiajs/react';
import { Plus, UserCog } from 'lucide-react';
import { useState } from 'react';

import AddAdminUserModal from '@/components/admin/administrator/add-admin-user-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { AdminUser } from '@/types/administrator';

/** Reference: cats-frontend/src/pages/admin/administratorTabs/AdminUsersTab.tsx */
export default function AdminUsersTab({
    adminUsers,
}: {
    adminUsers: AdminUser[];
}) {
    const [search, setSearch] = useState('');
    const [modalOpen, setModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<AdminUser | null>(null);

    const filtered = adminUsers.filter((user) => {
        const term = search.toLowerCase();

        return (
            user.first_name.toLowerCase().includes(term) ||
            user.last_name.toLowerCase().includes(term) ||
            user.email.toLowerCase().includes(term)
        );
    });

    const openCreate = () => {
        setEditingUser(null);
        setModalOpen(true);
    };

    const openEdit = (user: AdminUser) => {
        setEditingUser(user);
        setModalOpen(true);
    };

    const deactivate = (user: AdminUser) => {
        router.patch(
            `/admin/users/${user.id}/admin-update`,
            { is_active: !user.is_active },
            { preserveScroll: true },
        );
    };

    return (
        <div className="grid grid-cols-1 gap-5">
            <div className="grid grid-cols-3 gap-3">
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">Total</p>
                    <p className="text-2xl font-bold">{adminUsers.length}</p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">Active</p>
                    <p className="text-2xl font-bold">
                        {adminUsers.filter((user) => user.is_active).length}
                    </p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">Inactive</p>
                    <p className="text-2xl font-bold">
                        {adminUsers.filter((user) => !user.is_active).length}
                    </p>
                </Card>
            </div>

            <Card className="rounded-[10px] p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div className="flex-1">
                        <Input
                            placeholder="Search by name or email..."
                            className="rounded-[10px]"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                    <Button className="rounded-[10px]" onClick={openCreate}>
                        <Plus /> Add Admin User
                    </Button>
                </div>
            </Card>

            <Card className="p-6">
                <div className="w-full overflow-x-auto">
                    <table className="w-full">
                        <thead className="border-b text-left text-sm text-muted-foreground">
                            <tr>
                                <th className="pb-3">Name</th>
                                <th className="pb-3">Email</th>
                                <th className="hidden pb-3 md:table-cell">
                                    Phone
                                </th>
                                <th className="pb-3">Status</th>
                                <th className="pb-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.map((user) => (
                                <tr
                                    key={user.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="py-3">
                                        {user.first_name} {user.last_name}
                                    </td>
                                    <td className="py-3">{user.email}</td>
                                    <td className="hidden py-3 md:table-cell">
                                        {user.phone ?? '-'}
                                    </td>
                                    <td className="py-3">
                                        <Badge
                                            className={`rounded-[5px] ${
                                                user.is_active
                                                    ? 'border border-green-400 bg-green-100 text-green-700'
                                                    : 'border border-gray-400 bg-gray-100 text-gray-700'
                                            }`}
                                        >
                                            {user.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </td>
                                    <td className="py-3">
                                        <div className="flex gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="rounded-[5px]"
                                                onClick={() => openEdit(user)}
                                            >
                                                <UserCog className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="rounded-[5px]"
                                                onClick={() => deactivate(user)}
                                            >
                                                {user.is_active
                                                    ? 'Deactivate'
                                                    : 'Activate'}
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>

            <AddAdminUserModal
                adminUser={editingUser}
                isOpen={modalOpen}
                onClose={() => setModalOpen(false)}
            />
        </div>
    );
}

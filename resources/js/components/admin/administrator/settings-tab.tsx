import { useForm } from '@inertiajs/react';

import ChangePasswordCard from '@/components/admin/administrator/change-password-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { AdminProfile } from '@/types/administrator';

interface SettingsForm {
    first_name: string;
    last_name: string;
    phone: string;
    new_intake: boolean;
    invoice_payments: boolean;
    session_reminders: boolean;
    new_applications: boolean;
}

/** Reference: cats-frontend/src/pages/admin/administratorTabs/SettingsTab.tsx */
export default function SettingsTab({ profile }: { profile: AdminProfile }) {
    const { data, setData, put, processing, errors } = useForm<SettingsForm>({
        first_name: profile.first_name,
        last_name: profile.last_name,
        phone: profile.phone ?? '',
        new_intake: profile.new_intake,
        invoice_payments: profile.invoice_payments,
        session_reminders: profile.session_reminders,
        new_applications: profile.new_applications,
    });

    const submit = () => {
        put('/admin/administrator/profile', { preserveScroll: true });
    };

    return (
        <div className="grid grid-cols-1 gap-5">
            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <p className="font-bold text-primary md:col-span-2">
                        Profile
                    </p>

                    <div>
                        <Label htmlFor="settings-first-name">First Name</Label>
                        <Input
                            id="settings-first-name"
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
                        <Label htmlFor="settings-last-name">Last Name</Label>
                        <Input
                            id="settings-last-name"
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
                        <Label htmlFor="settings-email">Email</Label>
                        <Input
                            id="settings-email"
                            value={profile.email}
                            disabled
                            className="mt-2 rounded-[10px]"
                        />
                    </div>
                    <div>
                        <Label htmlFor="settings-phone">Phone</Label>
                        <Input
                            id="settings-phone"
                            value={data.phone}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('phone', event.target.value)
                            }
                        />
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <p className="font-bold text-primary">
                        Notification Preferences
                    </p>

                    <div className="flex items-center justify-between">
                        <Label>New Intake Applications</Label>
                        <Switch
                            checked={data.new_intake}
                            onCheckedChange={(checked) =>
                                setData('new_intake', checked)
                            }
                        />
                    </div>
                    <div className="flex items-center justify-between">
                        <Label>Invoice Payments</Label>
                        <Switch
                            checked={data.invoice_payments}
                            onCheckedChange={(checked) =>
                                setData('invoice_payments', checked)
                            }
                        />
                    </div>
                    <div className="flex items-center justify-between">
                        <Label>Session Reminders</Label>
                        <Switch
                            checked={data.session_reminders}
                            onCheckedChange={(checked) =>
                                setData('session_reminders', checked)
                            }
                        />
                    </div>
                    <div className="flex items-center justify-between">
                        <Label>New Job Applications</Label>
                        <Switch
                            checked={data.new_applications}
                            onCheckedChange={(checked) =>
                                setData('new_applications', checked)
                            }
                        />
                    </div>

                    <div className="flex justify-end">
                        <Button
                            className="rounded-[10px]"
                            onClick={submit}
                            disabled={processing}
                        >
                            Save Changes
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <ChangePasswordCard />
        </div>
    );
}

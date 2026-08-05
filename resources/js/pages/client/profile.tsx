import { Head, useForm } from '@inertiajs/react';
import { User } from 'lucide-react';

import ChangePasswordCard from '@/components/admin/administrator/change-password-card';
import DocumentsTab from '@/components/admin/client/documents-tab';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import ClientLayout from '@/layouts/client-layout';
import { getInitials } from '@/lib/helpers';
import type { Client } from '@/types/client';

interface ClientProfileProps {
    client: Client;
}

interface ProfileForm {
    child_first_name: string;
    child_last_name: string;
    primary_parent_phone: string;
    date_of_birth: string;
    street_address: string;
    address_line_2: string;
    city: string;
    state_province: string;
    postal_code: string;
    emergency_contact_name: string;
    emergency_contact_phone: string;
}

/** Reference: cats-frontend/src/pages/client/ProfilePage.tsx — email stays disabled, all other intake fields are editable. */
export default function ClientProfile({ client }: ClientProfileProps) {
    const intake = client.original_intake;
    const childName =
        `${intake?.child_first_name ?? ''} ${intake?.child_last_name ?? ''}`.trim();

    const { data, setData, put, processing, errors } = useForm<ProfileForm>({
        child_first_name: intake?.child_first_name ?? '',
        child_last_name: intake?.child_last_name ?? '',
        primary_parent_phone: intake?.primary_parent_phone ?? '',
        date_of_birth: intake?.date_of_birth ?? '',
        street_address: intake?.street_address ?? '',
        address_line_2: intake?.address_line_2 ?? '',
        city: intake?.city ?? '',
        state_province: intake?.state_province ?? '',
        postal_code: intake?.postal_code ?? '',
        emergency_contact_name: intake?.emergency_contact_name ?? '',
        emergency_contact_phone: intake?.emergency_contact_phone ?? '',
    });

    const submit = () => {
        put('/client/profile');
    };

    return (
        <>
            <Head title="My Profile" />

            <div className="space-y-6 p-6">
                <div className="flex items-center gap-3">
                    <User className="h-8 w-8 text-primary" />
                    <div>
                        <h1 className="text-3xl font-bold">My Profile</h1>
                        <p className="text-muted-foreground">
                            Manage your profile and documents
                        </p>
                    </div>
                </div>

                <Card className="p-6">
                    <div className="mb-6 flex items-center gap-4">
                        <Avatar className="h-20 w-20">
                            <AvatarFallback className="bg-primary/10 text-2xl text-primary">
                                {getInitials(childName)}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <h2 className="text-2xl font-bold">
                                {childName || 'Client'}
                            </h2>
                            <p className="text-muted-foreground">
                                {intake?.primary_parent_email}
                            </p>
                            <Badge
                                variant="secondary"
                                className="mt-1 rounded bg-primary/10 text-primary"
                            >
                                Client
                            </Badge>
                        </div>
                    </div>

                    <Tabs defaultValue="profile" className="w-full">
                        <TabsList className="grid w-full grid-cols-3">
                            <TabsTrigger value="profile">
                                Profile Information
                            </TabsTrigger>
                            <TabsTrigger value="security">Security</TabsTrigger>
                            <TabsTrigger value="documents">
                                Documents
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent
                            value="profile"
                            className="grid grid-cols-1 gap-5 pt-5"
                        >
                            <Card className="rounded-[10px]">
                                <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                                    <div>
                                        <Label htmlFor="profile-child-first-name">
                                            Child First Name
                                        </Label>
                                        <Input
                                            id="profile-child-first-name"
                                            value={data.child_first_name}
                                            className="mt-2 rounded-[10px]"
                                            onChange={(event) =>
                                                setData(
                                                    'child_first_name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        {errors.child_first_name && (
                                            <p className="mt-1 text-sm text-destructive">
                                                {errors.child_first_name}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <Label htmlFor="profile-child-last-name">
                                            Child Last Name
                                        </Label>
                                        <Input
                                            id="profile-child-last-name"
                                            value={data.child_last_name}
                                            className="mt-2 rounded-[10px]"
                                            onChange={(event) =>
                                                setData(
                                                    'child_last_name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        {errors.child_last_name && (
                                            <p className="mt-1 text-sm text-destructive">
                                                {errors.child_last_name}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <Label htmlFor="profile-email">
                                            Email
                                        </Label>
                                        <Input
                                            id="profile-email"
                                            type="email"
                                            value={
                                                intake?.primary_parent_email ??
                                                ''
                                            }
                                            disabled
                                            className="mt-2 rounded-[10px]"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="profile-phone">
                                            Phone
                                        </Label>
                                        <Input
                                            id="profile-phone"
                                            value={data.primary_parent_phone}
                                            className="mt-2 rounded-[10px]"
                                            onChange={(event) =>
                                                setData(
                                                    'primary_parent_phone',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="profile-dob">
                                            Date of Birth
                                        </Label>
                                        <Input
                                            id="profile-dob"
                                            type="date"
                                            value={data.date_of_birth}
                                            className="mt-2 rounded-[10px]"
                                            onChange={(event) =>
                                                setData(
                                                    'date_of_birth',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>

                                    <div className="md:col-span-2">
                                        <Label htmlFor="profile-street">
                                            Street Address
                                        </Label>
                                        <Input
                                            id="profile-street"
                                            value={data.street_address}
                                            className="mt-2 rounded-[10px]"
                                            onChange={(event) =>
                                                setData(
                                                    'street_address',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="md:col-span-2">
                                        <Label htmlFor="profile-address-2">
                                            Address Line 2
                                        </Label>
                                        <Input
                                            id="profile-address-2"
                                            value={data.address_line_2}
                                            className="mt-2 rounded-[10px]"
                                            onChange={(event) =>
                                                setData(
                                                    'address_line_2',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="profile-city">
                                            City
                                        </Label>
                                        <Input
                                            id="profile-city"
                                            value={data.city}
                                            className="mt-2 rounded-[10px]"
                                            onChange={(event) =>
                                                setData(
                                                    'city',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="profile-province">
                                            Province
                                        </Label>
                                        <Input
                                            id="profile-province"
                                            value={data.state_province}
                                            className="mt-2 rounded-[10px]"
                                            onChange={(event) =>
                                                setData(
                                                    'state_province',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="profile-postal">
                                            Postal Code
                                        </Label>
                                        <Input
                                            id="profile-postal"
                                            value={data.postal_code}
                                            className="mt-2 rounded-[10px]"
                                            onChange={(event) =>
                                                setData(
                                                    'postal_code',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="profile-emergency-name">
                                            Emergency Contact Name
                                        </Label>
                                        <Input
                                            id="profile-emergency-name"
                                            value={data.emergency_contact_name}
                                            className="mt-2 rounded-[10px]"
                                            onChange={(event) =>
                                                setData(
                                                    'emergency_contact_name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="profile-emergency-phone">
                                            Emergency Contact Phone
                                        </Label>
                                        <Input
                                            id="profile-emergency-phone"
                                            value={data.emergency_contact_phone}
                                            className="mt-2 rounded-[10px]"
                                            onChange={(event) =>
                                                setData(
                                                    'emergency_contact_phone',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="flex justify-end">
                                <Button
                                    className="rounded-[10px]"
                                    onClick={submit}
                                    disabled={processing}
                                >
                                    Save Changes
                                </Button>
                            </div>
                        </TabsContent>

                        <TabsContent value="security" className="pt-5">
                            <ChangePasswordCard />
                        </TabsContent>

                        <TabsContent value="documents" className="pt-5">
                            <DocumentsTab client={client} isPreview />
                        </TabsContent>
                    </Tabs>
                </Card>
            </div>
        </>
    );
}

ClientProfile.layout = (page: React.ReactNode) => (
    <ClientLayout>{page}</ClientLayout>
);

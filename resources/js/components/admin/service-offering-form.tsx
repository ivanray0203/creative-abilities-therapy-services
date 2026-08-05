import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { ServiceOffering } from '@/types/client';

const SERVICE_TYPES: { value: string; label: string }[] = [
    { value: 'general_service', label: 'General Service' },
    { value: 'specific_service', label: 'Specific Service' },
    { value: 'non_direct_service', label: 'Non-Direct Service' },
];

interface ServiceOfferingFormData {
    name: string;
    code: string;
    type: string;
    description: string;
    base_price: string;
    is_active: boolean;
}

function initialValues(
    service?: ServiceOffering | null,
): ServiceOfferingFormData {
    return {
        name: service?.name ?? '',
        code: service?.code ?? '',
        type: service?.type ?? 'general_service',
        description: service?.description ?? '',
        base_price: service?.base_price ?? '',
        is_active: service?.is_active ?? true,
    };
}

/** Shared create/edit form for the admin "Service Offerings" catalog. */
export default function ServiceOfferingForm({
    service,
}: {
    service?: ServiceOffering | null;
}) {
    const isEdit = service != null;

    const { data, setData, post, put, processing, errors } =
        useForm<ServiceOfferingFormData>(initialValues(service));

    const submit = () => {
        if (isEdit && service) {
            put(`/admin/services/${service.id}`);

            return;
        }

        post('/admin/services');
    };

    return (
        <div className="space-y-6">
            <Card>
                <CardContent className="space-y-5 p-6">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <Label htmlFor="so-name">
                                Name <span className="text-red-700">*</span>
                            </Label>
                            <Input
                                id="so-name"
                                value={data.name}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                            />
                            {errors.name && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="so-code">
                                Code <span className="text-red-700">*</span>
                            </Label>
                            <Input
                                id="so-code"
                                value={data.code}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('code', event.target.value)
                                }
                            />
                            {errors.code && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.code}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <Label>
                                Type <span className="text-red-700">*</span>
                            </Label>
                            <Select
                                value={data.type}
                                onValueChange={(value) =>
                                    setData('type', value)
                                }
                            >
                                <SelectTrigger className="mt-2 rounded-[10px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {SERVICE_TYPES.map((type) => (
                                        <SelectItem
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.type && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.type}
                                </p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="so-price">Base Price</Label>
                            <Input
                                id="so-price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.base_price}
                                className="mt-2 rounded-[10px]"
                                onChange={(event) =>
                                    setData('base_price', event.target.value)
                                }
                            />
                            {errors.base_price && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.base_price}
                                </p>
                            )}
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="so-description">Description</Label>
                        <Textarea
                            id="so-description"
                            value={data.description}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-red-600">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center justify-between">
                        <Label htmlFor="so-active">Active</Label>
                        <Switch
                            id="so-active"
                            checked={data.is_active}
                            onCheckedChange={(checked) =>
                                setData('is_active', checked)
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
                    <Save /> {isEdit ? 'Save Changes' : 'Add Service'}
                </Button>
            </div>
        </div>
    );
}

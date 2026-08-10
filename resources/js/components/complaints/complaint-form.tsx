import { useForm } from '@inertiajs/react';
import { AlertCircleIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatScheduledDateTime } from '@/lib/helpers';
import { sessionServiceLabel } from '@/lib/sessions';
import type { ComplaintCategory } from '@/types/complaint';
import type { ScheduleSession } from '@/types/session';

const CATEGORIES: { value: ComplaintCategory; label: string }[] = [
    { value: 'scheduling', label: 'Scheduling' },
    { value: 'billing', label: 'Billing' },
    { value: 'quality', label: 'Quality of Service' },
    { value: 'communication', label: 'Communication' },
    { value: 'other', label: 'Other' },
];

const CONSENT_TEXT: Record<'client' | 'therapist', string> = {
    client: 'I understand this complaint will be reviewed by CATS administration and may be shared with the relevant therapist as part of the review process.',
    therapist:
        'I understand this complaint will be reviewed by CATS administration and handled in accordance with organizational policy.',
};

interface ComplaintFormData {
    session_id: string;
    subject: string;
    category: ComplaintCategory | '';
    description: string;
    consent_given: boolean;
    file: File | null;
}

/** Reference: cats-frontend/src/forms/ComplaintForm.tsx */
export default function ComplaintForm({
    role,
    basePath,
    sessionsUrl,
}: {
    role: 'therapist' | 'client';
    basePath: string;
    sessionsUrl: string;
}) {
    const [sessions, setSessions] = useState<ScheduleSession[]>([]);

    const { data, setData, post, processing, errors } =
        useForm<ComplaintFormData>({
            session_id: '',
            subject: '',
            category: '',
            description: '',
            consent_given: false,
            file: null,
        });

    useEffect(() => {
        fetch(sessionsUrl)
            .then((response) => response.json())
            .then(setSessions)
            .catch(() => setSessions([]));
    }, [sessionsUrl]);

    const submit = () => {
        post(`${basePath}`, {
            forceFormData: true,
        });
    };

    return (
        <div className="grid grid-cols-1 gap-5 p-6">
            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5">
                    <div>
                        <Label htmlFor="complaint-session">Session *</Label>
                        <Select
                            value={data.session_id}
                            onValueChange={(value) =>
                                setData('session_id', value)
                            }
                        >
                            <SelectTrigger
                                id="complaint-session"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue placeholder="Select the session this is about" />
                            </SelectTrigger>
                            <SelectContent>
                                {sessions.map((session) => (
                                    <SelectItem
                                        key={session.id}
                                        value={String(session.id)}
                                    >
                                        {formatScheduledDateTime(
                                            session.scheduled_start,
                                        )}{' '}
                                        —{' '}
                                        {sessionServiceLabel(
                                            session,
                                            'Session',
                                        )}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.session_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.session_id}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="complaint-subject">Subject *</Label>
                        <Input
                            id="complaint-subject"
                            value={data.subject}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('subject', event.target.value)
                            }
                        />
                        {errors.subject && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.subject}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="complaint-category">Category *</Label>
                        <Select
                            value={data.category}
                            onValueChange={(value) =>
                                setData('category', value as ComplaintCategory)
                            }
                        >
                            <SelectTrigger
                                id="complaint-category"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue placeholder="Select category" />
                            </SelectTrigger>
                            <SelectContent>
                                {CATEGORIES.map((category) => (
                                    <SelectItem
                                        key={category.value}
                                        value={category.value}
                                    >
                                        {category.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.category && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.category}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="complaint-description">
                            Description *
                        </Label>
                        <Textarea
                            id="complaint-description"
                            rows={5}
                            value={data.description}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="complaint-file">
                            Attachment (optional, max 10MB)
                        </Label>
                        <Input
                            id="complaint-file"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('file', event.target.files?.[0] ?? null)
                            }
                        />
                        {errors.file && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.file}
                            </p>
                        )}
                    </div>

                    <div className="rounded-[5px] border border-blue-400 bg-blue-50 p-4">
                        <p className="flex flex-row gap-3 text-sm text-blue-800">
                            <AlertCircleIcon className="h-5 w-5 shrink-0" />
                            {CONSENT_TEXT[role]}
                        </p>
                        <label className="mt-3 flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={data.consent_given}
                                onCheckedChange={(checked) =>
                                    setData('consent_given', checked === true)
                                }
                            />
                            I acknowledge and agree to the above consent terms.
                        </label>
                        {errors.consent_given && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.consent_given}
                            </p>
                        )}
                    </div>

                    <div className="flex justify-end">
                        <Button
                            className="rounded-[10px]"
                            onClick={submit}
                            disabled={processing}
                        >
                            File Complaint
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

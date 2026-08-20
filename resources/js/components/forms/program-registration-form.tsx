import { useForm, usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { ProgramDetail } from '@/types/program';

interface RegistrationFormData {
    participant_first_name: string;
    participant_last_name: string;
    participant_date_of_birth: string;
    parent_name: string;
    parent_email: string;
    parent_phone: string;
    notes: string;
}

const EMPTY_FORM: RegistrationFormData = {
    participant_first_name: '',
    participant_last_name: '',
    participant_date_of_birth: '',
    parent_name: '',
    parent_email: '',
    parent_phone: '',
    notes: '',
};

/**
 * Public sign-up for one program. Posts to
 * `public.programs.register`, which re-checks capacity and the closing date —
 * a place can be taken while this form sits open.
 */
export default function ProgramRegistrationForm({
    program,
}: {
    program: ProgramDetail;
}) {
    const { props } = usePage<{
        flash?: { success?: string };
        /*
         * `program` is not a field on this form, so useForm's typed errors
         * never carry it. The shared error bag does.
         */
        errors: Record<string, string>;
    }>();
    const { data, setData, post, processing, errors, reset } =
        useForm<RegistrationFormData>(EMPTY_FORM);
    const programError = props.errors?.program;

    const submit = () => {
        post(`/programs/${program.slug}/register`, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    if (props.flash?.success) {
        return (
            <div
                id="program-registration-success"
                className="rounded-[10px] border border-primary/30 bg-primary/5 p-6 text-center"
            >
                <CheckCircle2 className="mx-auto h-8 w-8 text-primary" />
                <p className="mt-3 font-semibold text-charcoal-gray">
                    Registration received
                </p>
                <p className="mt-2 text-sm text-muted-foreground">
                    {props.flash.success}
                </p>
                <p className="mt-2 text-sm text-muted-foreground">
                    Our team will be in touch to confirm the place.
                </p>
            </div>
        );
    }

    if (!program.is_open) {
        return (
            <div className="rounded-[10px] border bg-muted/40 p-6 text-center">
                <p className="font-semibold text-charcoal-gray">
                    Registration is closed
                </p>
                <p className="mt-2 text-sm text-muted-foreground">
                    This program is either full or past its closing date.
                    Contact us and we will let you know when it next runs.
                </p>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            {/* Raised by the server when the last place goes while this form
                is open, so it belongs above the fields rather than beside one. */}
            {programError && (
                <p className="rounded-[10px] bg-destructive/10 p-3 text-sm text-destructive md:col-span-2">
                    {programError}
                </p>
            )}

            <div>
                <Label htmlFor="participant-first-name">
                    Child&apos;s First Name *
                </Label>
                <Input
                    id="participant-first-name"
                    value={data.participant_first_name}
                    className="mt-2 rounded-[10px]"
                    onChange={(event) =>
                        setData('participant_first_name', event.target.value)
                    }
                />
                {errors.participant_first_name && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.participant_first_name}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor="participant-last-name">
                    Child&apos;s Last Name *
                </Label>
                <Input
                    id="participant-last-name"
                    value={data.participant_last_name}
                    className="mt-2 rounded-[10px]"
                    onChange={(event) =>
                        setData('participant_last_name', event.target.value)
                    }
                />
                {errors.participant_last_name && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.participant_last_name}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor="participant-dob">Date of Birth</Label>
                <Input
                    id="participant-dob"
                    type="date"
                    value={data.participant_date_of_birth}
                    className="mt-2 rounded-[10px]"
                    onChange={(event) =>
                        setData('participant_date_of_birth', event.target.value)
                    }
                />
                {errors.participant_date_of_birth && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.participant_date_of_birth}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor="parent-name">Parent / Caregiver Name *</Label>
                <Input
                    id="parent-name"
                    value={data.parent_name}
                    className="mt-2 rounded-[10px]"
                    onChange={(event) =>
                        setData('parent_name', event.target.value)
                    }
                />
                {errors.parent_name && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.parent_name}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor="parent-email">Email *</Label>
                <Input
                    id="parent-email"
                    type="email"
                    value={data.parent_email}
                    className="mt-2 rounded-[10px]"
                    onChange={(event) =>
                        setData('parent_email', event.target.value)
                    }
                />
                {errors.parent_email && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.parent_email}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor="parent-phone">Phone *</Label>
                <Input
                    id="parent-phone"
                    value={data.parent_phone}
                    className="mt-2 rounded-[10px]"
                    onChange={(event) =>
                        setData('parent_phone', event.target.value)
                    }
                />
                {errors.parent_phone && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.parent_phone}
                    </p>
                )}
            </div>

            <div className="md:col-span-2">
                <Label htmlFor="registration-notes">
                    Anything we should know?
                </Label>
                <Textarea
                    id="registration-notes"
                    value={data.notes}
                    placeholder="Allergies, support needs, or anything else that would help us prepare."
                    className="mt-2 rounded-[10px]"
                    onChange={(event) => setData('notes', event.target.value)}
                />
                {errors.notes && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.notes}
                    </p>
                )}
            </div>

            <div className="md:col-span-2">
                <Button
                    id="program-register-submit"
                    type="button"
                    className="rounded-[5px]"
                    onClick={submit}
                    disabled={processing}
                >
                    {processing ? 'Submitting...' : 'Register'}
                </Button>
                <p className="mt-2 text-xs text-muted-foreground">
                    Registering does not charge you. We will confirm the place
                    and payment details by email.
                </p>
            </div>
        </div>
    );
}

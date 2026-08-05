import { useForm } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const STRENGTH_PATTERN =
    /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

/** Reference: cats-frontend's ChangePassCard, posts to the existing Breeze `password.update` route. */
export default function ChangePasswordCard() {
    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const strengthError =
        data.password.length > 0 && !STRENGTH_PATTERN.test(data.password)
            ? 'Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a digit, and a special character.'
            : null;

    const submit = () => {
        put('/password', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <Card className="rounded-[10px]">
            <CardContent className="grid grid-cols-1 gap-5 p-5">
                <p className="font-bold text-primary">Change Password</p>

                <div>
                    <Label htmlFor="current-password">Current Password</Label>
                    <Input
                        id="current-password"
                        type="password"
                        value={data.current_password}
                        className="mt-2 rounded-[10px]"
                        onChange={(event) =>
                            setData('current_password', event.target.value)
                        }
                    />
                    {errors.current_password && (
                        <p className="mt-1 text-sm text-destructive">
                            {errors.current_password}
                        </p>
                    )}
                </div>

                <div>
                    <Label htmlFor="new-password">New Password</Label>
                    <Input
                        id="new-password"
                        type="password"
                        value={data.password}
                        className="mt-2 rounded-[10px]"
                        onChange={(event) =>
                            setData('password', event.target.value)
                        }
                    />
                    {strengthError && (
                        <p className="mt-1 text-sm text-destructive">
                            {strengthError}
                        </p>
                    )}
                    {errors.password && (
                        <p className="mt-1 text-sm text-destructive">
                            {errors.password}
                        </p>
                    )}
                </div>

                <div>
                    <Label htmlFor="confirm-password">
                        Confirm New Password
                    </Label>
                    <Input
                        id="confirm-password"
                        type="password"
                        value={data.password_confirmation}
                        className="mt-2 rounded-[10px]"
                        onChange={(event) =>
                            setData('password_confirmation', event.target.value)
                        }
                    />
                </div>

                <div className="flex justify-end">
                    <Button
                        className="rounded-[10px]"
                        onClick={submit}
                        disabled={processing || !!strengthError}
                    >
                        Update Password
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

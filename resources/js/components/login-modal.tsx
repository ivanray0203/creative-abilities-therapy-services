import { Link, useForm } from '@inertiajs/react';
import {
    Eye,
    EyeOff,
    Loader2,
    Lock,
    LogIn,
    Mail,
    Shield,
    ShieldCheck,
} from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function LoginModal({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({
            email: '',
            password: '',
            remember: false,
        });

    const closeAndReset = () => {
        reset();
        clearErrors();
        setShowPassword(false);
        onOpenChange(false);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login', {
            onSuccess: closeAndReset,
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) =>
                next ? onOpenChange(true) : closeAndReset()
            }
        >
            <DialogContent className="gap-0 overflow-hidden p-0 sm:max-w-md [&>button]:text-white [&>button]:opacity-90 [&>button]:hover:opacity-100">
                <div className="bg-gradient-to-br from-primary to-primary/80 p-6 text-white">
                    <div className="flex items-start gap-4">
                        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/20">
                            <Shield className="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <DialogTitle className="text-2xl font-bold text-white">
                                Welcome Back
                            </DialogTitle>
                            <DialogDescription className="text-white/90">
                                Sign in to access the admin portal
                            </DialogDescription>
                        </div>
                    </div>
                </div>

                <div className="space-y-5 p-6">
                    <form onSubmit={submit} className="space-y-5">
                        <div className="space-y-2">
                            <Label
                                htmlFor="modal-email"
                                className="flex items-center gap-2"
                            >
                                <Mail className="h-4 w-4" />
                                Email Address
                            </Label>
                            <Input
                                id="modal-email"
                                type="email"
                                autoComplete="username"
                                placeholder="admin@easytechinnovations.com"
                                className="rounded-[5px] border-2 focus-visible:border-primary focus-visible:ring-0"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                            />
                            {errors.email && (
                                <p className="text-sm text-destructive">
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label
                                htmlFor="modal-password"
                                className="flex items-center gap-2"
                            >
                                <Lock className="h-4 w-4" />
                                Password
                            </Label>
                            <div className="relative">
                                <Input
                                    id="modal-password"
                                    type={showPassword ? 'text' : 'password'}
                                    autoComplete="current-password"
                                    placeholder="Enter your password"
                                    className="rounded-[5px] pr-9"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword((v) => !v)}
                                    className="absolute top-2.5 right-3 text-muted-foreground"
                                    aria-label={
                                        showPassword
                                            ? 'Hide password'
                                            : 'Show password'
                                    }
                                >
                                    {showPassword ? (
                                        <EyeOff className="h-4 w-4" />
                                    ) : (
                                        <Eye className="h-4 w-4" />
                                    )}
                                </button>
                            </div>
                            {errors.password && (
                                <p className="text-sm text-destructive">
                                    {errors.password}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="modal-remember"
                                    checked={data.remember}
                                    onCheckedChange={(checked) =>
                                        setData('remember', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="modal-remember"
                                    className="cursor-pointer font-normal"
                                >
                                    Remember me
                                </Label>
                            </div>
                            <Link
                                href="/forgot-password"
                                onClick={closeAndReset}
                                className="text-sm font-medium text-primary hover:underline"
                            >
                                Forgot password?
                            </Link>
                        </div>

                        <Button
                            type="submit"
                            className="w-full rounded-[5px] bg-primary text-base hover:bg-primary/90"
                            disabled={processing}
                        >
                            {processing ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <>
                                    <LogIn className="h-4 w-4" />
                                    Sign In to Portal
                                </>
                            )}
                        </Button>
                    </form>

                    <div className="flex items-start gap-2 rounded-[5px] bg-muted/50 p-3 text-xs text-muted-foreground">
                        <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <p>
                            Your connection is secure and encrypted. This portal
                            is protected by industry-standard security measures.
                        </p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

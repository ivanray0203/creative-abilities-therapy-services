import { Head, useForm } from '@inertiajs/react';
import { Loader2, Mail, Send } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PublicLayout from '@/layouts/public-layout';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <>
            <Head title="Forgot Password" />
            <div className="container mx-auto flex min-h-[70vh] items-center justify-center py-16">
                <Card className="w-full max-w-md">
                    <CardHeader className="space-y-1 text-center">
                        <CardTitle className="text-2xl">
                            Forgot your password?
                        </CardTitle>
                        <CardDescription>
                            Enter your email and we&apos;ll send you a link to
                            reset it.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {status && (
                            <p className="mb-4 text-sm font-medium text-green-600">
                                {status}
                            </p>
                        )}

                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <div className="relative">
                                    <Mail className="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="email"
                                        type="email"
                                        autoComplete="username"
                                        className="pl-9"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                    />
                                </div>
                                {errors.email && (
                                    <p className="text-sm text-destructive">
                                        {errors.email}
                                    </p>
                                )}
                            </div>

                            <Button
                                type="submit"
                                className="w-full rounded-[5px]"
                                disabled={processing}
                            >
                                {processing ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <>
                                        <Send className="mr-2 h-4 w-4" />
                                        Send reset link
                                    </>
                                )}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ForgotPassword.layout = (page: React.ReactNode) => (
    <PublicLayout>{page}</PublicLayout>
);

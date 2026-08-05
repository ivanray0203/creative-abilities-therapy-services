import { Head, Link } from '@inertiajs/react';
import {
    ArrowBigLeft,
    Book,
    CheckCircle,
    Cookie,
    SearchIcon,
    Star,
    Users,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import { tempServices } from '@/lib/content/services-fallback';
import type { Service } from '@/types/service';

/**
 * Ported 1:1 from cats-frontend/src/pages/ServiceDetail.tsx. The reference
 * fetches the service client-side via servicesAPI.getById() and falls back
 * to a static `tempServices` entry (by id) on failure; here the controller
 * already does the DB lookup server-side (ServiceController::show) and
 * passes `service: null` when not found, so the same by-id fallback lookup
 * against `tempServices` is done here instead, using the `serviceId` prop.
 */
export default function ServiceDetail({
    service,
    serviceId,
}: {
    service: Service | null;
    serviceId: number;
}) {
    const resolved: Service | undefined =
        service ?? tempServices.find((s) => s.id === serviceId);

    return (
        <>
            <Head title={resolved?.name ?? 'Service Details'} />

            {/* Header Section */}
            <section className="bg-secondary/80 py-2">
                <div className="container mx-auto pb-20">
                    <Button
                        asChild
                        className="mt-10 mb-5 rounded-full bg-white px-3 py-1 text-sm font-normal text-primary"
                    >
                        <Link href="/">
                            <ArrowBigLeft /> Back to Home
                        </Link>
                    </Button>
                    <div className="grid items-center gap-12 lg:grid-cols-2">
                        <div className="flex flex-col space-y-6">
                            <div className="flex flex-row gap-3 text-primary">
                                <p className="flex flex-row rounded-lg bg-primary/10 p-2">
                                    <SearchIcon /> Service Details
                                </p>
                            </div>

                            <p className="text-primary">{resolved?.name}</p>

                            <p className="text-lg">
                                {' '}
                                {resolved?.short_description}
                            </p>

                            <div>
                                <Button
                                    asChild
                                    className="rounded-s bg-primary"
                                >
                                    <Link href="/intake/apply">
                                        Request This Service
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        <div className="relative">
                            <div className="aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                                <img
                                    src={resolved?.photo ?? '/placeholder.svg'}
                                    alt="Therapist working with child"
                                    className="h-full w-full object-cover"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="bg-secondary/30 py-20">
                <div className="px-10 lg:px-60">
                    {/* Overview */}
                    <div>
                        <div className="flex flex-row gap-3 text-primary">
                            <p className="flex flex-row gap-3 rounded-lg bg-primary/10 p-3">
                                <Book /> Overview
                            </p>
                        </div>
                        <p className="mt-5 text-primary">About This Service</p>
                        <div className="mt-4 rounded-sm bg-secondary/80 p-8 text-lg">
                            <p>{resolved?.description}</p>
                        </div>
                    </div>

                    {/* Who we help */}
                    <div className="mt-20">
                        <div className="flex flex-row gap-3 text-primary">
                            <p className="flex flex-row gap-3 rounded-lg bg-primary/10 p-3">
                                <Users /> Who We Help
                            </p>
                        </div>
                        <p className="mt-5 text-primary">Who Can Benefit</p>
                        <div className="p mt-4 rounded-sm text-lg">
                            <div className="grid gap-3 sm:grid-cols-2">
                                {resolved?.benefits?.map((ben) => (
                                    <div
                                        key={ben}
                                        className="flex items-center gap-3 rounded-xl border-gray-400 p-4 shadow-md"
                                    >
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/15">
                                            <CheckCircle className="h-5 w-5 text-primary" />
                                        </div>
                                        <span className="font-medium text-primary">
                                            {ben}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* What We Provide */}
                    <div className="mt-20">
                        <div className="flex flex-row gap-3 text-primary">
                            <p className="flex flex-row gap-3 rounded-lg bg-primary/10 p-3">
                                <Users /> What we Provide
                            </p>
                        </div>
                        <p className="mt-5 text-primary">What We Offer</p>
                        <div className="p mt-4 rounded-sm text-lg">
                            <div className="grid gap-3 sm:grid-cols-1">
                                {resolved?.offerings?.map((off) => (
                                    <div
                                        key={off}
                                        className="flex items-center gap-3 rounded-xl border-gray-400 bg-secondary/20 p-4 py-8 shadow-md"
                                    >
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary">
                                            <CheckCircle className="h-5 w-5 bg-primary text-white" />
                                        </div>
                                        <span className="font-medium text-primary">
                                            {off}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Approach and Expected Outcomes */}
                <div className="mt-20 flex flex-col gap-10 px-10 md:flex-row">
                    <Card className="flex-grow">
                        <CardTitle className="flex flex-row gap-3 bg-secondary/80 p-10 text-primary">
                            <Cookie /> Our Approach
                        </CardTitle>

                        <CardContent className="m-0">
                            <div className="mt-10">
                                {resolved?.approaches.map((ap) => (
                                    <div
                                        key={ap}
                                        className="flex items-center gap-3 py-2"
                                    >
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full">
                                            <CheckCircle className="h-5 w-5 text-primary" />
                                        </div>
                                        <span className="font-medium">
                                            {ap}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="flex-grow">
                        <CardTitle className="m-0 flex flex-row gap-3 bg-secondary/80 p-10 text-primary">
                            {' '}
                            <Star /> Expected Outcomes
                        </CardTitle>
                        <CardContent className="m-0">
                            <div className="mt-10">
                                {resolved?.approaches.map((ap) => (
                                    <div
                                        key={ap}
                                        className="flex items-center gap-3 py-2"
                                    >
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full">
                                            <CheckCircle className="h-5 w-5 text-primary" />
                                        </div>
                                        <span className="font-medium">
                                            {ap}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </>
    );
}

ServiceDetail.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            show_ready: true,
            show_contact: true,
            title: '',
            desc: '',
            is_career: false,
        }}
    >
        {page}
    </PublicLayout>
);

import { Head, Link, router } from '@inertiajs/react';
import {
    Clock,
    DollarSign,
    Edit,
    Eye,
    ListChecks,
    Shield,
    User,
    UserCheck,
    XCircle,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import {
    FundingBadge,
    IntakeStatusBadge,
} from '@/components/admin/intake/badges';
import PaginationFooter from '@/components/pagination-footer';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import type { Intake, IntakeStats, Paginated } from '@/types/intake';

interface IntakeIndexProps {
    intakes: Paginated<Intake>;
    stats: IntakeStats;
    filters: { search: string; funding: string };
}

/**
 * Admin intake list, ported from cats-frontend/src/pages/admin/IntakePage.tsx.
 * Search, funding filter and pagination run server-side through Inertia rather
 * than client-side over the whole table.
 */
export default function AdminIntakeIndex({
    intakes,
    stats,
    filters,
}: IntakeIndexProps) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/intake',
                { search, funding: filters.funding },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyFunding = (funding: string) => {
        router.get(
            '/admin/intake',
            { search, funding },
            { preserveState: true, replace: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            '/admin/intake',
            { search, funding: filters.funding, page },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Intake Management" />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-0">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                            Intake Management
                        </h1>
                        <p className="text-sm text-muted-foreground sm:text-base">
                            Review and process new intake applications
                        </p>
                    </div>
                    <Button className="mt-2 rounded-[5px] sm:mt-0" asChild>
                        <Link href="/admin/intake/create">
                            <span className="mr-2">+</span>
                            New Intake
                        </Link>
                    </Button>
                </div>

                <div className="grid grid-cols-2 gap-2 sm:gap-4 md:grid-cols-4 md:gap-6">
                    <StatCard
                        label="Pending Review"
                        value={stats.pending}
                        icon={
                            <Clock className="h-5 w-5 text-yellow-600 sm:h-6 sm:w-6" />
                        }
                        tone="bg-yellow-100"
                    />
                    <StatCard
                        label="Under Review"
                        value={stats.under_review}
                        icon={
                            <Eye className="h-5 w-5 text-blue-600 sm:h-6 sm:w-6" />
                        }
                        tone="bg-blue-100"
                    />
                    <StatCard
                        label="Waitlist"
                        value={stats.waitlist}
                        icon={
                            <ListChecks className="h-5 w-5 text-violet-600 sm:h-6 sm:w-6" />
                        }
                        tone="bg-violet-100"
                    />
                    <StatCard
                        label="Denied"
                        value={stats.denied}
                        icon={
                            <XCircle className="h-5 w-5 text-red-600 sm:h-6 sm:w-6" />
                        }
                        tone="bg-red-100"
                    />
                </div>

                <div className="rounded-sm border border-primary bg-gradient-to-br from-secondary-orange/5 to-transparent p-4 shadow-md sm:p-5">
                    <p className="flex items-center gap-2 text-sm sm:gap-3 sm:text-base">
                        <DollarSign className="h-4 w-4 text-primary sm:h-5 sm:w-5" />{' '}
                        Funding Type Overview
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground sm:mt-2 sm:text-sm">
                        Distribution of funding sources across intake
                        applications
                    </p>

                    <div className="mt-4 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 md:gap-6">
                        <FundingCard
                            label="FSCD"
                            value={stats.fscd}
                            icon={
                                <DollarSign className="h-4 w-4 text-blue-600 sm:h-5 sm:w-5" />
                            }
                            tone="bg-blue-100"
                        />
                        <FundingCard
                            label="Private Insurance"
                            value={stats.insurance}
                            icon={
                                <Shield className="h-4 w-4 text-green-600 sm:h-5 sm:w-5" />
                            }
                            tone="bg-green-100"
                        />
                        <FundingCard
                            label="Private Pay"
                            value={stats.private}
                            icon={
                                <User className="h-4 w-4 text-gray-600 sm:h-5 sm:w-5" />
                            }
                            tone="bg-gray-100"
                        />
                    </div>
                </div>

                <Card className="rounded-[10px] p-4">
                    <div className="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-4">
                        <div className="w-full flex-1">
                            <label
                                htmlFor="intake-search"
                                className="mb-1 block text-sm font-medium text-muted-foreground"
                            >
                                Search
                            </label>
                            <Input
                                id="intake-search"
                                placeholder="Search by client or child name..."
                                className="w-full rounded-[10px]"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                            />
                        </div>

                        <div className="w-full sm:w-auto">
                            <label
                                htmlFor="intake-funding"
                                className="mb-1 block text-sm font-medium text-muted-foreground"
                            >
                                Funding Type
                            </label>
                            <Select
                                value={filters.funding}
                                onValueChange={applyFunding}
                            >
                                <SelectTrigger
                                    id="intake-funding"
                                    className="w-full rounded-[10px] sm:w-auto"
                                >
                                    <SelectValue placeholder="Funding Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    <SelectItem value="fscd">FSCD</SelectItem>
                                    <SelectItem value="Insurance">
                                        Insurance
                                    </SelectItem>
                                    <SelectItem value="private">
                                        Private
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="mb-4">
                        <h3 className="text-lg font-semibold">
                            Pending Intake Applications
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            <span className="block sm:hidden">
                                Intakes awaiting review. View more details on a
                                larger screen.
                            </span>
                            <span className="hidden sm:block">
                                Intakes awaiting review, approval, or in
                                waitlist (Approved intakes are in Clients
                                section)
                            </span>
                        </p>
                    </div>

                    {intakes.data.length === 0 ? (
                        <div className="py-8 text-center text-muted-foreground">
                            No intake applications found
                        </div>
                    ) : (
                        <div className="w-full overflow-x-auto">
                            <table className="w-full">
                                <thead className="border-b">
                                    <tr className="text-left">
                                        <th className="hidden pb-3 font-medium text-muted-foreground md:table-cell">
                                            ID
                                        </th>
                                        <th className="hidden pb-3 font-medium text-muted-foreground md:table-cell">
                                            Client Name
                                        </th>
                                        <th className="pb-3 text-sm font-medium text-muted-foreground md:text-base">
                                            Child Name
                                        </th>
                                        <th className="hidden pb-3 font-medium text-muted-foreground md:table-cell">
                                            Funding Type
                                        </th>
                                        <th className="pb-3 text-sm font-medium text-muted-foreground md:text-base">
                                            Date Submitted
                                        </th>
                                        <th className="hidden pb-3 font-medium text-muted-foreground md:table-cell">
                                            Status
                                        </th>
                                        <th className="pb-3 text-sm font-medium text-muted-foreground md:text-base">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {intakes.data.map((intake) => (
                                        <tr
                                            key={intake.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="hidden py-4 md:table-cell">
                                                #{intake.id}
                                            </td>
                                            <td className="hidden py-4 md:table-cell">
                                                {intake.primary_parent_name}
                                            </td>
                                            <td className="py-4 text-xs md:text-base">
                                                <span>
                                                    {intake.child_first_name}{' '}
                                                    {intake.child_last_name}
                                                </span>
                                                <p className="block sm:hidden">
                                                    <IntakeStatusBadge
                                                        intake={intake}
                                                    />
                                                </p>
                                            </td>
                                            <td className="hidden py-4 md:table-cell">
                                                <FundingBadge
                                                    fundingSource={
                                                        intake.funding_source
                                                    }
                                                />
                                            </td>
                                            <td className="py-4 text-xs md:text-base">
                                                {
                                                    intake.created_at?.split(
                                                        'T',
                                                    )[0]
                                                }
                                            </td>
                                            <td className="hidden py-4 md:table-cell">
                                                <IntakeStatusBadge
                                                    intake={intake}
                                                />
                                            </td>
                                            <td className="py-4">
                                                <div className="flex flex-row md:gap-2">
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        className="text-sm text-primary"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/admin/intake/${intake.id}`}
                                                        >
                                                            <span className="block sm:hidden">
                                                                <Eye />
                                                            </span>
                                                            <span className="hidden sm:block">
                                                                View Details
                                                            </span>
                                                        </Link>
                                                    </Button>

                                                    {intake.approved_as_client ? (
                                                        <Badge
                                                            variant="secondary"
                                                            className="gap-1"
                                                        >
                                                            <UserCheck className="h-3 w-3" />
                                                            Already Approved
                                                        </Badge>
                                                    ) : (
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/admin/intake/${intake.id}/edit`}
                                                            >
                                                                <Edit className="h-4 w-4 sm:mr-1" />
                                                                <span className="hidden sm:block">
                                                                    Edit
                                                                </span>
                                                            </Link>
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            <PaginationFooter
                                className="mt-4"
                                currentPage={intakes.current_page}
                                lastPage={intakes.last_page}
                                perPage={intakes.per_page}
                                total={intakes.total}
                                countOnPage={intakes.data.length}
                                onPageChange={goToPage}
                            />
                        </div>
                    )}
                </Card>
            </div>
        </>
    );
}

function StatCard({
    label,
    value,
    icon,
    tone,
}: {
    label: string;
    value: number;
    icon: React.ReactNode;
    tone: string;
}) {
    return (
        <Card className="flex flex-col justify-between p-4 sm:p-5 md:p-6">
            <div className="flex items-center justify-between gap-2 sm:gap-3">
                <div>
                    <p className="text-xs text-muted-foreground sm:text-sm">
                        {label}
                    </p>
                    <p className="text-2xl font-bold sm:text-3xl">{value}</p>
                </div>
                <div className={`rounded-full p-2 sm:p-3 ${tone}`}>{icon}</div>
            </div>
        </Card>
    );
}

function FundingCard({
    label,
    value,
    icon,
    tone,
}: {
    label: string;
    value: number;
    icon: React.ReactNode;
    tone: string;
}) {
    return (
        <Card className="flex items-center gap-2 p-3 sm:gap-3 sm:p-4 md:p-6">
            <div className={`flex-shrink-0 rounded-full p-2 sm:p-3 ${tone}`}>
                {icon}
            </div>
            <div className="truncate">
                <p className="truncate text-xs text-muted-foreground sm:text-sm">
                    {label}
                </p>
                <p className="truncate text-lg font-bold sm:text-xl">{value}</p>
            </div>
        </Card>
    );
}

AdminIntakeIndex.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);

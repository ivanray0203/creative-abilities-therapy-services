import { Link } from '@inertiajs/react';
import { Book, Cookie } from 'lucide-react';
import * as LucideIcons from 'lucide-react';

import { Button } from '@/components/ui/button';
import type { Career } from '@/types/career';

interface CareerModalProps {
    career: Career;
    visible: boolean;
    onClose: () => void;
}

/**
 * Quick-view modal ported 1:1 from cats-frontend/src/modals/CareerDetailModal.tsx.
 * Triggered by the "View Details" button on each career card in
 * resources/js/pages/public/careers.tsx.
 */
export default function CareerDetailModal({
    career,
    visible,
    onClose,
}: CareerModalProps) {
    if (!visible || !career) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div className="relative mx-4 max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-xl bg-white shadow-xl">
                {/* Close Button */}
                <button
                    className="absolute top-4 right-4 text-xl font-bold text-gray-500 hover:text-gray-700"
                    onClick={onClose}
                >
                    ✕
                </button>

                <div className="flex flex-row gap-5 p-10">
                    <div>
                        <div className="relative flex items-center justify-center overflow-hidden rounded-sm bg-primary p-5 shadow-md transition-transform duration-300 hover:scale-105">
                            <div className="absolute inset-0 bg-white/10 opacity-40 blur-xl" />
                            <LucideIcons.Brain className="relative z-10 h-10 w-10 text-white" />
                        </div>
                    </div>

                    {/* Career Info */}
                    <div className="flex flex-col gap-4 sm:gap-2">
                        {/* Career Title */}
                        <p className="text-2xl font-semibold text-primary">
                            {career.position}
                        </p>

                        <div className="flex flex-row gap-5">
                            <p className="flex items-center gap-2 text-base sm:text-lg">
                                <LucideIcons.MapPin className="h-5 w-5 text-primary" />
                                {career.location}
                            </p>
                            <p className="flex items-center gap-2 text-base sm:text-lg">
                                <LucideIcons.Calendar className="h-5 w-5 text-primary" />
                                {career.hours}
                            </p>
                        </div>

                        <p className="flex items-center gap-2 text-base sm:text-lg">
                            <LucideIcons.DollarSign className="h-5 w-5 text-primary" />
                            {career.rate}
                        </p>
                    </div>
                </div>

                <div className="space-y-5 p-6 sm:p-5">
                    {/* Overview */}
                    <div>
                        <div className="flex items-center gap-2 font-light text-primary sm:px-6">
                            <Book className="h-4 w-4 sm:h-5 sm:w-5" />
                            <p>Position Overview</p>
                        </div>
                        <div className="overflow-y-auto p-5">
                            <p className="text-sm leading-relaxed sm:text-lg">
                                {career.about_description}
                            </p>
                        </div>
                    </div>

                    <div>
                        <div className="flex items-center gap-2 px-5 font-light text-primary">
                            <Cookie className="h-4 w-4 sm:h-5 sm:w-5" />
                            <p>Key Reponsibilities</p>
                        </div>
                        <div className="p-5">
                            <div>
                                {career.responsibilities.map((r) => (
                                    <div
                                        key={r}
                                        className="flex items-center gap-2 py-1 sm:gap-3 sm:py-2"
                                    >
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full sm:h-8 sm:w-8">
                                            <LucideIcons.CheckCircle className="h-4 w-4 text-primary sm:h-5 sm:w-5" />
                                        </div>
                                        <span className="text-sm sm:text-base">
                                            {r}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div>
                        <div className="flex items-center gap-2 px-5 font-light text-primary">
                            <Cookie className="h-4 w-4 sm:h-5 sm:w-5" />
                            <p>Qualifications</p>
                        </div>
                        <div className="p-5">
                            <div>
                                {career.qualifications.map((r) => (
                                    <div
                                        key={r}
                                        className="flex items-center gap-2 py-1 sm:gap-3 sm:py-2"
                                    >
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full sm:h-8 sm:w-8">
                                            <LucideIcons.CheckCircle className="h-4 w-4 text-primary sm:h-5 sm:w-5" />
                                        </div>
                                        <span className="text-sm sm:text-base">
                                            {r}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Apply Button */}
                <div className="sticky bottom-0 z-10 flex justify-end border-t border-gray-200 bg-white p-6">
                    <Button
                        asChild
                        className="flex items-center gap-2 rounded px-4 py-2 font-medium text-white sm:px-6 sm:py-3"
                    >
                        <Link
                            href={`/careers/apply/${career.id}?position=${career.position}`}
                        >
                            Apply for This Position{' '}
                            <LucideIcons.ArrowRight className="h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    );
}

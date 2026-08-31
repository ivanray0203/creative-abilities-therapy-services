import * as LucideIcons from 'lucide-react';

import ScrollToContactButton from '@/components/scroll-to-contact-button';
import { Card, CardContent } from '@/components/ui/card';
import type { LeaderEntry } from '@/lib/content/team';
import { cn } from '@/lib/utils';

/**
 * A leadership profile: a portrait beside the intro copy, then the
 * responsibilities behind the role and a closing commitment statement. Used
 * for every leader except the founder, whose card carries credentials too.
 *
 * `imageOnRight` alternates the portrait down the page so consecutive
 * profiles don't stack into one column of images.
 */
export default function LeaderProfile({
    leader,
    imageOnRight = false,
}: {
    leader: LeaderEntry;
    imageOnRight?: boolean;
}) {
    return (
        <section
            id={`leader-${leader.id}`}
            className="border-t border-secondary-orange/20 py-16"
        >
            <div className="container mx-auto space-y-10">
                <div className="grid items-center gap-12 lg:grid-cols-2">
                    {/* Portrait */}
                    <div
                        className={cn(
                            'relative w-full',
                            imageOnRight && 'lg:order-last',
                        )}
                    >
                        <div className="aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                            <img
                                src={leader.photo}
                                alt={leader.name}
                                className="h-full w-full object-cover"
                            />
                        </div>
                    </div>

                    {/* Intro */}
                    <div className="space-y-5">
                        <div>
                            <p className="text-xl font-semibold text-primary md:text-2xl">
                                {leader.name}
                            </p>
                            <p className="pt-2 text-lg">{leader.position}</p>
                        </div>

                        <h2 className="text-lg font-semibold text-charcoal-gray sm:text-xl">
                            {leader.tagline}
                        </h2>

                        {leader.paragraphs.map((paragraph) => (
                            <p key={paragraph} className="leading-relaxed">
                                {paragraph}
                            </p>
                        ))}
                    </div>
                </div>

                <div className="space-y-6">
                    <h3 className="font-semibold text-primary">
                        His Role at CATS
                    </h3>

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {leader.roles.map((role) => (
                            <Card
                                key={role.title}
                                className="border-primary/20 bg-secondary-orange/5"
                            >
                                <CardContent className="space-y-2 p-6">
                                    <p className="font-semibold text-primary">
                                        {role.title}
                                    </p>
                                    <p className="text-sm">
                                        {role.description}
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    <Card className="border-primary/20 bg-secondary/30">
                        <CardContent className="space-y-3 p-6">
                            <p className="font-semibold text-primary">
                                {leader.commitmentTitle}
                            </p>
                            <p className="text-sm leading-relaxed">
                                {leader.commitment}
                            </p>
                        </CardContent>
                    </Card>

                    <div className="flex flex-row gap-3">
                        <ScrollToContactButton className="rounded">
                            <LucideIcons.Mail /> {leader.contactLabel}
                        </ScrollToContactButton>
                    </div>
                </div>
            </div>
        </section>
    );
}

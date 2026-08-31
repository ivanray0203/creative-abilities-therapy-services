import { Head } from '@inertiajs/react';
import { AccordionItem } from '@radix-ui/react-accordion';
import { ArrowRight, FileQuestion, Search } from 'lucide-react';
import { useState } from 'react';

import {
    Accordion,
    AccordionContent,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import PublicLayout from '@/layouts/public-layout';
import { faqData } from '@/lib/content/faq';

/**
 * Ported 1:1 from cats-frontend/src/pages/Faq.tsx renderAnswer(). Renders a
 * lightweight markdown-lite: line breaks become paragraphs, bare URLs become
 * links, **bold** becomes <strong>, and tab characters become indentation.
 */
function renderAnswer(text: string) {
    const lines = text.split('\n');

    return lines.map((line, index) => {
        const linkParts = line.split(/(https?:\/\/[^\s]+)/g);

        return (
            <p key={index} className="mb-2">
                {linkParts.map((part, i) => {
                    if (part.match(/^https?:\/\/[^\s]+$/)) {
                        return (
                            <a
                                key={i}
                                href={part}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-blue-600 underline"
                            >
                                {part}
                            </a>
                        );
                    }

                    const boldParts = part.split(/(\*\*[^*]+\*\*)/g);

                    return boldParts.map((b, j) => {
                        if (b.startsWith('**') && b.endsWith('**')) {
                            return (
                                <strong key={j} className="font-semibold">
                                    {b.replace(/\*\*/g, '')}
                                </strong>
                            );
                        }

                        return b.split('\t').map((tPart, k) => (
                            <span key={k} style={{ marginLeft: k * 16 }}>
                                {tPart}
                            </span>
                        ));
                    });
                })}
            </p>
        );
    });
}

const CATEGORIES = [
    'Services & Billing',
    'FSCD',
    'Getting Started',
    'Privacy & Safety',
];

export default function Faq() {
    const [searchQuery, setSearchQuery] = useState('');
    const [activeCategory, setActiveCategory] = useState<string | null>(null);

    const filteredFAQs = faqData
        .filter((category) =>
            activeCategory ? category.category === activeCategory : true,
        )
        .map((category) => ({
            ...category,
            questions: category.questions.filter(
                (q) =>
                    q.question
                        .toLowerCase()
                        .includes(searchQuery.toLowerCase()) ||
                    q.answer.toLowerCase().includes(searchQuery.toLowerCase()),
            ),
        }))
        .filter((category) => category.questions.length > 0);

    return (
        <>
            <Head title="Frequently Asked Questions" />

            {/* Header */}
            <section
                id="header"
                className="bg-gradient-to-b from-secondary-orange/10 to-transparent"
            >
                <div className="flex flex-col items-center justify-center px-4 pt-10 pb-40 sm:px-6 md:px-20">
                    {/* Tag */}
                    <div className="rounded-sm bg-secondary-orange/5 p-2 sm:p-3">
                        <p className="flex items-center gap-2 text-sm sm:text-base">
                            <FileQuestion className="h-5 w-5 text-primary sm:h-6 sm:w-6" />
                            <span className="font-medium text-primary">
                                Questions & Answers
                            </span>
                        </p>
                    </div>

                    {/* Title */}
                    <p className="mt-5 text-center text-xl font-semibold text-primary sm:text-2xl">
                        Frequently Asked Questions
                    </p>

                    {/* Description */}
                    <p className="mt-3 max-w-3xl text-center text-base leading-relaxed sm:text-lg md:text-xl">
                        Find answers to common questions about our services,
                        intake process, FSCD, billing, privacy, and more. If you
                        don&rsquo;t see the information you&rsquo;re looking
                        for, our team is happy to help.
                    </p>
                </div>
            </section>

            {/* Search Bar */}
            <div className="relative mx-auto w-full max-w-xl px-4 sm:px-6">
                <Search className="absolute top-1/2 left-10 h-5 w-5 -translate-y-1/2 transform text-muted-foreground sm:h-6 sm:w-6" />

                <Input
                    type="text"
                    placeholder="Search questions..."
                    className="h-12 w-full rounded-lg border-border bg-card pl-12 text-base shadow-sm transition focus:border-primary focus:ring-2 focus:ring-primary sm:h-14 sm:pl-14 sm:text-lg"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    aria-label="Search questions"
                />
            </div>

            {/* Category Filter Buttons */}
            <section className="mt-10 mb-8 flex flex-wrap justify-center gap-3 px-4 sm:gap-4">
                {CATEGORIES.map((cat) => (
                    <button
                        key={cat}
                        onClick={() =>
                            setActiveCategory(
                                cat === activeCategory ? null : cat,
                            )
                        }
                        className={`rounded-full border px-4 py-2 text-sm font-medium transition hover:border-primary hover:bg-secondary-orange/10 hover:text-primary sm:px-6 ${
                            activeCategory === cat
                                ? 'border-primary bg-primary text-white'
                                : 'border-border bg-card text-foreground hover:border-primary'
                        }`}
                    >
                        {cat}
                    </button>
                ))}
            </section>

            <section className="px-4 py-16 sm:px-6 md:px-8">
                <div className="mx-auto max-w-4xl">
                    {filteredFAQs.length === 0 ? (
                        <div className="py-12 text-center">
                            <p className="text-base text-muted-foreground sm:text-lg">
                                No questions found matching your search. Try
                                different keywords.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-12">
                            {filteredFAQs.map((category, categoryIndex) => (
                                <div key={categoryIndex} className="space-y-6">
                                    <h2 className="border-b border-border pb-3 text-xl font-bold text-foreground sm:text-2xl">
                                        {category.category}
                                    </h2>

                                    <Accordion
                                        type="single"
                                        collapsible
                                        className="space-y-3 sm:space-y-4"
                                    >
                                        {category.questions.map(
                                            (item, index) => (
                                                <AccordionItem
                                                    key={index}
                                                    value={`${categoryIndex}-${index}`}
                                                    className="rounded-lg border border-border bg-card px-4 shadow-sm transition-shadow duration-200 hover:border-primary hover:shadow-md sm:px-6"
                                                >
                                                    <AccordionTrigger className="group py-4 text-left sm:py-6">
                                                        <div className="flex flex-row gap-3">
                                                            <Badge className="bg-secondary-orange/10 text-primary">
                                                                {
                                                                    category.category
                                                                }
                                                            </Badge>
                                                            <span className="pr-4 text-sm font-semibold text-foreground group-hover:text-primary">
                                                                {item.question}
                                                            </span>
                                                        </div>
                                                    </AccordionTrigger>
                                                    <AccordionContent className="pt-2 pb-4 text-sm leading-relaxed whitespace-pre-line sm:pb-6 sm:text-base">
                                                        {renderAnswer(
                                                            item.answer,
                                                        )}

                                                        {item.link && (
                                                            <a
                                                                href={
                                                                    item.link
                                                                        .href
                                                                }
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="group mt-2 inline-flex items-center gap-2 font-medium text-primary underline"
                                                            >
                                                                {
                                                                    item.link
                                                                        .label
                                                                }
                                                                <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                                            </a>
                                                        )}
                                                    </AccordionContent>
                                                </AccordionItem>
                                            ),
                                        )}
                                    </Accordion>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </section>
        </>
    );
}

Faq.layout = (page: React.ReactNode) => (
    <PublicLayout
        footer={{
            is_career: false,
            show_ready: false,
            show_contact: true,
            title: 'Ready to Access FSCD Services?',
            desc: 'If your family is eligible for FSCD support and you are seeking specialized services for your child, contact Creative Abilities Therapy Services today. Our team will help you navigate the FSCD process, from application to service delivery, ensuring your child receives the best possible care.',
        }}
    >
        {page}
    </PublicLayout>
);

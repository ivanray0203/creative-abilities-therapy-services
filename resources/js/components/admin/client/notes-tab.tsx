import { router, useForm } from '@inertiajs/react';
import { Plus, ReceiptText, Trash } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import type { Client } from '@/types/client';

/** Reference: cats-frontend/src/pages/admin/clientTabs/Notes.tsx */
export default function NotesTab({
    client,
    isPreview,
}: {
    client: Client;
    isPreview?: boolean;
}) {
    const { data, setData, patch, processing, reset, errors } = useForm({
        note: '',
    });

    const notes = client.clinical_notes ?? [];

    const addNote = () => {
        if (!data.note.trim()) {
            return;
        }

        patch(`/admin/clients/${client.id}/notes`, {
            preserveScroll: true,
            onSuccess: () => reset('note'),
        });
    };

    const deleteNote = (noteId: string) => {
        router.delete(`/admin/clients/${client.id}/notes/${noteId}`, {
            preserveScroll: true,
        });
    };

    return (
        <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <p className="flex flex-row items-center gap-3">
                        Clinical Notes
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Team notes and comments about this client
                    </p>

                    <div className="mt-10 grid grid-cols-1 gap-5">
                        {notes.length > 0 ? (
                            notes.map((note) => (
                                <div
                                    key={note.id}
                                    className="flex flex-row justify-between rounded-sm border p-3"
                                >
                                    <div className="flex flex-row items-start gap-3">
                                        <div className="flex rounded-[5px] bg-secondary-orange/10 p-3 text-primary">
                                            <ReceiptText />
                                        </div>

                                        <div>
                                            <p>{note.user}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {note.date} {note.time}
                                            </p>
                                            <p className="mt-4">{note.note}</p>
                                        </div>
                                    </div>

                                    {!isPreview && (
                                        <div className="flex gap-3">
                                            <Button
                                                variant="outline"
                                                className="rounded-[5px]"
                                                onClick={() =>
                                                    deleteNote(note.id)
                                                }
                                            >
                                                <Trash />
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            ))
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No notes to show
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>

            {!isPreview && (
                <Card className="rounded-[10px]">
                    <CardContent className="p-5">
                        <p className="flex flex-row items-center gap-3">
                            Add New Note
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Add notes and comments for your team
                        </p>

                        <Textarea
                            className="mt-2 w-full rounded-[10px]"
                            placeholder="Add a note about this client"
                            rows={2}
                            value={data.note}
                            onChange={(event) =>
                                setData('note', event.target.value)
                            }
                        />
                        {errors.note && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.note}
                            </p>
                        )}

                        <Button
                            className="mt-5 rounded-[5px]"
                            onClick={addNote}
                            disabled={processing}
                        >
                            <Plus /> Add Note
                        </Button>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}

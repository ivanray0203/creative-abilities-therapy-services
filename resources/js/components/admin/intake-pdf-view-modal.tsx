import { useRef } from 'react';
import { useReactToPrint } from 'react-to-print';

import DocumentsTab from '@/components/admin/intake/documents-tab';
import FamilyTab from '@/components/admin/intake/family-tab';
import HistoryTab from '@/components/admin/intake/history-tab';
import NotesTab from '@/components/admin/intake/notes-tab';
import OverviewTab from '@/components/admin/intake/overview-tab';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Intake } from '@/types/intake';

/**
 * Browser-print PDF export, ported from cats-frontend/src/modals/IntakePDFViewModal.tsx.
 * Renders the same five tabs in `isPreview` mode (interactive buttons suppressed).
 */
export default function IntakePdfViewModal({
    intake,
    isOpen,
    onClose,
}: {
    intake: Intake;
    isOpen: boolean;
    onClose: () => void;
}) {
    const printRef = useRef<HTMLDivElement>(null);

    const handlePrint = useReactToPrint({
        contentRef: printRef,
        documentTitle: `Intake_${intake.child_first_name}_${intake.child_last_name}_Intake_Form`,
    });

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="flex h-[90vh] w-full max-w-[80%] flex-col p-0">
                <div className="flex-1 overflow-y-auto p-6">
                    <div ref={printRef} className="m-3">
                        <DialogHeader className="mb-4">
                            <DialogTitle>Intake ID - {intake.id}</DialogTitle>
                        </DialogHeader>

                        <OverviewTab intake={intake} isPreview />
                        <FamilyTab intake={intake} isPreview />
                        <DocumentsTab intake={intake} isPreview />
                        <HistoryTab intake={intake} isPreview />
                        <NotesTab intake={intake} isPreview />
                    </div>
                </div>

                <DialogFooter className="flex gap-4 border-t bg-white p-4">
                    <Button
                        variant="outline"
                        className="flex-1 rounded"
                        onClick={onClose}
                    >
                        Close
                    </Button>
                    <Button
                        className="flex-1 rounded bg-primary text-white"
                        onClick={() => handlePrint()}
                    >
                        Export / Print PDF
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

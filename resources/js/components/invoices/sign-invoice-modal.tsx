import { router } from '@inertiajs/react';
import { Eraser } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const CANVAS_WIDTH = 620;
const CANVAS_HEIGHT = 200;

/**
 * Draw-your-own-signature pad for a parent returning an invoice.
 *
 * Hand-rolled on a canvas rather than pulling in a signature library — the
 * whole job is pointer events and lineTo, and the project's dependencies are
 * not ours to add to.
 */
export default function SignInvoiceModal({
    invoiceId,
    parentName,
    isOpen,
    onClose,
}: {
    invoiceId: number;
    parentName: string;
    isOpen: boolean;
    onClose: () => void;
}) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const drawing = useRef(false);
    const [hasDrawn, setHasDrawn] = useState(false);
    const [processing, setProcessing] = useState(false);

    /*
     * The backing store is sized to the device pixel ratio so the signature
     * is not a blurry upscale by the time it reaches the PDF.
     */
    useEffect(() => {
        const canvas = canvasRef.current;

        if (!isOpen || !canvas) {
            return;
        }

        const ratio = window.devicePixelRatio || 1;
        canvas.width = CANVAS_WIDTH * ratio;
        canvas.height = CANVAS_HEIGHT * ratio;

        const context = canvas.getContext('2d');

        if (!context) {
            return;
        }

        context.scale(ratio, ratio);
        // dompdf composites the PNG over white, so a transparent background
        // would be fine — but a painted one keeps the pad readable on screen.
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
        context.lineWidth = 2.2;
        context.lineCap = 'round';
        context.lineJoin = 'round';
        context.strokeStyle = '#1a1a1a';

        setHasDrawn(false);
    }, [isOpen]);

    const positionOf = (event: React.PointerEvent<HTMLCanvasElement>) => {
        const rect = event.currentTarget.getBoundingClientRect();

        // The canvas is displayed at CSS size, which may differ from its
        // attribute size, so pointer coordinates need scaling back.
        return {
            x: ((event.clientX - rect.left) / rect.width) * CANVAS_WIDTH,
            y: ((event.clientY - rect.top) / rect.height) * CANVAS_HEIGHT,
        };
    };

    const start = (event: React.PointerEvent<HTMLCanvasElement>) => {
        const context = canvasRef.current?.getContext('2d');

        if (!context) {
            return;
        }

        event.currentTarget.setPointerCapture(event.pointerId);
        drawing.current = true;
        const { x, y } = positionOf(event);
        context.beginPath();
        context.moveTo(x, y);
    };

    const move = (event: React.PointerEvent<HTMLCanvasElement>) => {
        const context = canvasRef.current?.getContext('2d');

        if (!drawing.current || !context) {
            return;
        }

        const { x, y } = positionOf(event);
        context.lineTo(x, y);
        context.stroke();
        setHasDrawn(true);
    };

    const stop = () => {
        drawing.current = false;
    };

    const clear = () => {
        const canvas = canvasRef.current;
        const context = canvas?.getContext('2d');

        if (!canvas || !context) {
            return;
        }

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
        setHasDrawn(false);
    };

    const submit = () => {
        const canvas = canvasRef.current;

        if (!canvas || !hasDrawn) {
            return;
        }

        router.post(
            `/client/invoices/${invoiceId}/sign`,
            { signature: canvas.toDataURL('image/png') },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => onClose(),
            },
        );
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-2xl rounded-xl">
                <DialogHeader>
                    <DialogTitle>Sign this invoice</DialogTitle>
                    <DialogDescription>
                        Draw your signature below. It is added to the
                        Parent&apos;s Signature box on the invoice and sent
                        back to the clinic.
                    </DialogDescription>
                </DialogHeader>

                <div className="rounded-[10px] border border-dashed p-3">
                    <canvas
                        id="signature-pad"
                        ref={canvasRef}
                        className="h-[200px] w-full touch-none rounded-[6px] bg-white"
                        onPointerDown={start}
                        onPointerMove={move}
                        onPointerUp={stop}
                        onPointerLeave={stop}
                    />
                    <div className="mt-2 flex items-center justify-between">
                        <p className="text-xs text-muted-foreground">
                            {parentName}
                        </p>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="rounded-[5px]"
                            onClick={clear}
                        >
                            <Eraser className="h-4 w-4" /> Clear
                        </Button>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        id="submit-signature"
                        className="w-full rounded-[10px]"
                        onClick={submit}
                        disabled={!hasDrawn || processing}
                    >
                        {processing ? 'Sending...' : 'Sign and send back'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

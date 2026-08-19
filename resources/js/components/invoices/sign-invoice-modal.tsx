import { router } from '@inertiajs/react';
import { Eraser } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

/** Pad height in CSS pixels — kept in step with the canvas below. */
const CANVAS_HEIGHT = 200;

/**
 * Paints the pad's background and stroke style, in a coordinate space of
 * layout CSS pixels: the device-pixel scale lives in the transform, so a
 * point the pointer reports is a point the ink lands on.
 */
function primeContext(
    context: CanvasRenderingContext2D,
    width: number,
    height: number,
    ratio: number,
): void {
    // Set rather than multiply: sizing the backing store resets the
    // transform, but re-priming a cleared canvas does not.
    context.setTransform(ratio, 0, 0, ratio, 0, 0);
    // dompdf composites the PNG over white, so a transparent background
    // would be fine — but a painted one keeps the pad readable on screen.
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, width, height);
    context.lineWidth = 2.2;
    context.lineCap = 'round';
    context.lineJoin = 'round';
    context.strokeStyle = '#1a1a1a';
}

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
    const observerRef = useRef<ResizeObserver | null>(null);
    const drawing = useRef(false);
    const [hasDrawn, setHasDrawn] = useState(false);
    const [processing, setProcessing] = useState(false);

    /**
     * Sizes the backing store to the canvas's own layout box, times the
     * device pixel ratio.
     *
     * Both halves matter. The ratio keeps the signature from reaching the
     * PDF as a blurry upscale; measuring the real box — rather than assuming
     * a fixed width — is what keeps one drawing unit equal to one CSS pixel,
     * so the stroke follows the cursor at whatever width the dialog lands on.
     *
     * `clientWidth`/`clientHeight` are read instead of a bounding rect
     * because the dialog animates in on a scale transform: a rect measured
     * mid-animation is smaller than the box the pad settles at.
     */
    const resizeCanvas = useCallback((canvas: HTMLCanvasElement) => {
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        const ratio = window.devicePixelRatio || 1;
        const nextWidth = Math.round(width * ratio);
        const nextHeight = Math.round(height * ratio);

        if (
            nextWidth === 0 ||
            nextHeight === 0 ||
            (canvas.width === nextWidth && canvas.height === nextHeight)
        ) {
            return;
        }

        // Resizing wipes the backing store, so anything already drawn is
        // carried across rather than silently lost.
        const previous = document.createElement('canvas');
        previous.width = canvas.width;
        previous.height = canvas.height;
        previous.getContext('2d')?.drawImage(canvas, 0, 0);

        canvas.width = nextWidth;
        canvas.height = nextHeight;

        const context = canvas.getContext('2d');

        if (!context) {
            return;
        }

        primeContext(context, width, height, ratio);
        context.drawImage(previous, 0, 0, width, height);
    }, []);

    /*
     * Set up from a ref callback rather than an effect: the dialog mounts its
     * content through a portal, so an effect keyed on `isOpen` can run before
     * the canvas exists — which left the pad on its default 300x150 backing
     * store and the ink nowhere near the pointer.
     */
    const attachCanvas = useCallback(
        (canvas: HTMLCanvasElement | null) => {
            observerRef.current?.disconnect();
            observerRef.current = null;
            canvasRef.current = canvas;

            if (!canvas) {
                return;
            }

            resizeCanvas(canvas);
            setHasDrawn(false);

            // The pad is fluid, and the dialog is still settling when it
            // mounts, so its box is re-read whenever it changes.
            const observer = new ResizeObserver(() => resizeCanvas(canvas));
            observer.observe(canvas);
            observerRef.current = observer;
        },
        [resizeCanvas],
    );

    /**
     * Where the pointer is, in the canvas's drawing units — layout CSS
     * pixels. The rect is what the pointer's client coordinates are relative
     * to, so a transform on it (the dialog's open animation) is divided back
     * out rather than dragging the ink off the cursor.
     */
    const positionOf = (event: React.PointerEvent<HTMLCanvasElement>) => {
        const canvas = event.currentTarget;
        const rect = canvas.getBoundingClientRect();

        return {
            x: ((event.clientX - rect.left) / rect.width) * canvas.clientWidth,
            y: ((event.clientY - rect.top) / rect.height) * canvas.clientHeight,
        };
    };

    const start = (event: React.PointerEvent<HTMLCanvasElement>) => {
        const context = canvasRef.current?.getContext('2d');

        if (!context) {
            return;
        }

        // Capture keeps the stroke coming while the pointer wanders off the
        // pad. It throws for a pointer the browser no longer considers
        // active, which must not cost the user their stroke.
        try {
            event.currentTarget.setPointerCapture(event.pointerId);
        } catch {
            // Drawing works without it; only the off-pad tail is lost.
        }

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

    const stop = (event: React.PointerEvent<HTMLCanvasElement>) => {
        if (event.currentTarget.hasPointerCapture(event.pointerId)) {
            event.currentTarget.releasePointerCapture(event.pointerId);
        }

        drawing.current = false;
    };

    const clear = () => {
        const canvas = canvasRef.current;
        const context = canvas?.getContext('2d');

        if (!canvas || !context) {
            return;
        }

        primeContext(
            context,
            canvas.clientWidth,
            canvas.clientHeight,
            window.devicePixelRatio || 1,
        );
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
                        Parent&apos;s Signature box on the invoice and sent back
                        to the clinic.
                    </DialogDescription>
                </DialogHeader>

                <div className="rounded-[10px] border border-dashed p-3">
                    <canvas
                        id="signature-pad"
                        ref={attachCanvas}
                        style={{ height: `${CANVAS_HEIGHT}px` }}
                        className="w-full touch-none rounded-[6px] bg-white"
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

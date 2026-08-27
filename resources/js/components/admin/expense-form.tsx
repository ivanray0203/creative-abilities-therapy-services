import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Expense } from '@/types/expense';

interface ExpenseFormData {
    expense_date: string;
    category: string;
    payee: string;
    description: string;
    amount: string;
    tax_amount: string;
    payment_method: string;
    status: string;
}

function initialValues(expense?: Expense | null): ExpenseFormData {
    return {
        expense_date:
            expense?.expense_date?.slice(0, 10) ??
            new Date().toISOString().slice(0, 10),
        category: expense?.category ?? '',
        payee: expense?.payee ?? '',
        description: expense?.description ?? '',
        amount: expense?.amount ?? '',
        tax_amount: expense?.tax_amount ?? '',
        payment_method: expense?.payment_method ?? '',
        status: expense?.status ?? 'paid',
    };
}

/** Admin "Record/Edit Expense" form, backing the `expenses` table. */
export default function ExpenseForm({
    expense,
    categories,
    paymentMethods,
}: {
    expense?: Expense | null;
    categories: string[];
    paymentMethods: string[];
}) {
    const isEdit = expense != null;

    const { data, setData, post, put, processing, errors } =
        useForm<ExpenseFormData>(initialValues(expense));

    // Shown live so the person entering it can sanity-check against a receipt.
    const total = (
        (Number(data.amount) || 0) + (Number(data.tax_amount) || 0)
    ).toFixed(2);

    const submit = () => {
        if (isEdit && expense) {
            put(`/admin/expenses/${expense.id}`);

            return;
        }

        post('/admin/expenses');
    };

    return (
        <div className="grid grid-cols-1 gap-5 p-6">
            <Card className="rounded-[10px]">
                <CardContent className="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <p className="font-bold text-primary md:col-span-2">
                        Expense Details
                    </p>

                    <div>
                        <Label htmlFor="expense-date">Date *</Label>
                        <Input
                            id="expense-date"
                            type="date"
                            value={data.expense_date}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('expense_date', event.target.value)
                            }
                        />
                        {errors.expense_date && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.expense_date}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="expense-category">Category *</Label>
                        <Select
                            value={data.category}
                            onValueChange={(value) =>
                                setData('category', value)
                            }
                        >
                            <SelectTrigger
                                id="expense-category"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((category) => (
                                    <SelectItem key={category} value={category}>
                                        {category}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.category && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.category}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="expense-payee">Payee / Vendor *</Label>
                        <Input
                            id="expense-payee"
                            placeholder="e.g. Staples Canada"
                            value={data.payee}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('payee', event.target.value)
                            }
                        />
                        {errors.payee && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.payee}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="expense-payment-method">
                            Payment Method *
                        </Label>
                        <Select
                            value={data.payment_method}
                            onValueChange={(value) =>
                                setData('payment_method', value)
                            }
                        >
                            <SelectTrigger
                                id="expense-payment-method"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue placeholder="Select a method" />
                            </SelectTrigger>
                            <SelectContent>
                                {paymentMethods.map((method) => (
                                    <SelectItem key={method} value={method}>
                                        {method}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.payment_method && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.payment_method}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="expense-amount">
                            Amount (before GST) *
                        </Label>
                        <Input
                            id="expense-amount"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.amount}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('amount', event.target.value)
                            }
                        />
                        {errors.amount && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.amount}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="expense-tax">GST</Label>
                        <Input
                            id="expense-tax"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            value={data.tax_amount}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('tax_amount', event.target.value)
                            }
                        />
                        {errors.tax_amount && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.tax_amount}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="expense-status">Status *</Label>
                        <Select
                            value={data.status}
                            onValueChange={(value) => setData('status', value)}
                        >
                            <SelectTrigger
                                id="expense-status"
                                className="mt-2 rounded-[10px]"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="paid">Paid</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.status && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.status}
                            </p>
                        )}
                    </div>

                    <div className="flex items-end">
                        <div
                            id="expense-total"
                            className="w-full rounded-[10px] bg-secondary-orange/10 p-3 text-sm"
                        >
                            <span className="font-semibold text-primary">
                                Total:
                            </span>{' '}
                            ${total}
                        </div>
                    </div>

                    <div className="md:col-span-2">
                        <Label htmlFor="expense-description">Description</Label>
                        <Textarea
                            id="expense-description"
                            placeholder="What was this for?"
                            value={data.description}
                            className="mt-2 rounded-[10px]"
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.description}
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end">
                <Button
                    id="expense-submit"
                    className="rounded-[10px]"
                    onClick={submit}
                    disabled={processing}
                >
                    <Save /> {isEdit ? 'Save Changes' : 'Record Expense'}
                </Button>
            </div>
        </div>
    );
}

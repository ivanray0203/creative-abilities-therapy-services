import { Head } from '@inertiajs/react';

import ExpenseForm from '@/components/admin/expense-form';
import AdminLayout from '@/layouts/admin-layout';
import type { Expense } from '@/types/expense';

export default function AdminExpenseEdit({
    expense,
    categories,
    paymentMethods,
}: {
    expense: Expense;
    categories: string[];
    paymentMethods: string[];
}) {
    return (
        <>
            <Head title={`Edit ${expense.reference_number}`} />
            <ExpenseForm
                expense={expense}
                categories={categories}
                paymentMethods={paymentMethods}
            />
        </>
    );
}

AdminExpenseEdit.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);

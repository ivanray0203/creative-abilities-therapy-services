import { Head } from '@inertiajs/react';

import ExpenseForm from '@/components/admin/expense-form';
import AdminLayout from '@/layouts/admin-layout';

export default function AdminExpenseCreate({
    categories,
    paymentMethods,
}: {
    categories: string[];
    paymentMethods: string[];
}) {
    return (
        <>
            <Head title="Record Expense" />
            <ExpenseForm
                categories={categories}
                paymentMethods={paymentMethods}
            />
        </>
    );
}

AdminExpenseCreate.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);

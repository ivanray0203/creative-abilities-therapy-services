/**
 * Public consent-document shape, matching ConsentDocument/ConsentClause
 * Eloquent models (app/Models/ConsentDocument.php, ConsentClause.php).
 * Consumed by the intake application form/consent modal
 * (reference: src/types/consents.types.ts).
 */
export interface ConsentClause {
    id: number;
    document_id: number;
    order: number;
    text_template: string;
}

export interface ConsentDocument {
    id: number;
    title: string;
    purpose: string;
    version: string;
    is_active: boolean;
    effective_date: string;
    clauses: ConsentClause[];
}

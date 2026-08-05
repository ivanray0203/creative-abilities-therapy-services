/**
 * Shared admin/therapist/client complaint & dispute shapes, matching
 * `Complaint` (app/Models/Complaint.php) plus its eager-loaded relations.
 */

import type { Client } from '@/types/client';
import type { TherapistOption } from '@/types/intake';
import type { ScheduleSession } from '@/types/session';

export type ComplaintStatus = 'open' | 'under_review' | 'resolved';

export type ComplaintType = 'complaints' | 'disputes';

export type ComplaintCategory =
    'scheduling' | 'billing' | 'quality' | 'communication' | 'other';

export type ComplainedBy = 'client' | 'therapist';

export interface Complaint {
    id: number;
    client_id: number | null;
    subject: string | null;
    description: string | null;
    status: ComplaintStatus;
    type: ComplaintType;
    admin_response: string | null;
    category: ComplaintCategory | null;
    resolve_at: string | null;
    therapist_id: number | null;
    session_id: number | null;
    complained_by: ComplainedBy | null;
    consent_given: boolean;
    consent_info: string | null;
    consent_at: string | null;
    ip_address: string | null;
    file: string | null;
    drive_file_id: string | null;
    drive_file_url: string | null;
    drive_web_view: string | null;
    reviewed_at: string | null;
    reviewed_by_id: number | null;
    resolved_by_id: number | null;
    created_at: string;
    updated_at: string;
    client?: Client | null;
    therapist?: TherapistOption | null;
    session?: ScheduleSession | null;
    reviewed_by?: TherapistOption | null;
    resolved_by?: TherapistOption | null;
}

export interface ComplaintTypeStats {
    open: number;
    under_review: number;
    total: number;
}

export interface ComplaintStats {
    complaints: ComplaintTypeStats;
    disputes: ComplaintTypeStats;
}

export interface ComplaintFilters {
    type: 'all' | ComplaintType;
    status: 'all' | ComplaintStatus;
}

export type { Paginated } from '@/types/intake';

export type UserRole = 'admin' | 'therapist' | 'client' | 'staff' | 'guest';

export type User = {
    id: number;
    email: string;
    first_name: string;
    last_name: string;
    phone?: string | null;
    role: UserRole;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
};

/**
 * Minimal shape for now — the full profile (credentials, availability,
 * documents, etc.) is built out in Phase 11.
 */
export type TeamMember = {
    id: number;
    user_id: number;
    position: string | null;
    department: string;
    employment_status: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
    team_member: TeamMember | null;
    client_id: number | null;
};

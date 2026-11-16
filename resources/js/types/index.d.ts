import { Config } from "ziggy-js";

export interface LogFile {
    date: string;
    size: number;
    // Adiciona um objeto para armazenar a contagem de cada nível de log
    levels: Record<string, number>; // ex: { error: 10, info: 150 }
}

export interface LogEntry {
    timestamp: string;
    env: string;
    level:
        | "info"
        | "debug"
        | "notice"
        | "warning"
        | "error"
        | "critical"
        | "alert"
        | "emergency";
    message: string;
}
export interface ScheduledNotification {
    id: number;
    title: string;
    body: string;
    target_roles: string[];
    delivery_channels: string[];
    start_date: FormattedDate | null; // A API enviará a data como uma string no formato ISO
    period_in_days: number;
    is_active: boolean;
}
export interface UserTeam {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    avatar_url: string | null;
}
export interface User {
    id: number;
    name: string;
    email: string;
    avatar_url: string | null;
    is_master: boolean;
    is_active: boolean;
    is_active_text: string;

    created_at?: FormattedDate;
    updated_at?: FormattedDate;
    deleted_at?: FormattedDate;
    email_verified_at?: string;
    username: string | null;
    nickname: string | null;
    phone_number: string | null;
    birth_date: FormattedDate | null;
    is_active: boolean;
    is_active_text: string;
    addresses: Address[];
    notifications: Notification[];
    notification_count: number;
    can: string[];
    roles: Role[];
}
export interface CommandLog {
    id: number;
    command: string;
    output: string;
    status: boolean; // Pode ser mais específico se tiver outros status
    executed_at: FormattedDate;
}
export interface DeletedRecord {
    id: number;
    subject_type: string;
    subject_id: number;
    causer_type: string | null;
    causer_id: number | null;
    causer_name: string | null;
    subject_data: Record<string, any>;
    deleted_at: FormattedDate;
}
interface ActivityLog {
    id: number | string;
    subject_type: string;
    subject_type_alias: string; // 'Unknown' if alias does not exist
    event: string;
    subject_id: number | string;
    subject_name: string | null;
    causer_type: string | null;
    causer_id: number | string | null;
    causer_name: string | null;
    properties: Record<string, any>;
    causer: User;
    created_at?: FormattedDate;
    updated_at?: FormattedDate;
    description: string;
}

export interface Notification {
    id: string;
    data: {
        message: string;
        link: string;
    };
    read_at: string | null;
    created_at: string;
}
export type FlashMessage = {
    type?: "success" | "error";
    message: string | null;
};
export interface Crumb {
    label: string;
    url: string | null;
}
export type Theme = "light" | "dark";
export interface AppNotification {
    id: string;
    data: {
        title: string;
        message: string;
        link?: string;
        icon?: string; // 'alert-triangle', 'info', etc.
        type?: string; // 'moderation', 'reply', etc.
    };
    created_at_human: string;
    read_at: string | null;
}
export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>
> = T & {
    auth: {
        user: User;
        unread_notifications_count: number;
        notifications: AppNotification[];
    };
    ziggy: Config & { location: string };
    flash: FlashMessage;
    platformSettings?: PlatformSetting | null;
    theme: Theme;
    locale: string;
    appName: string;
    breadcrumbs: Crumb[];
    active_company?: {
        id: number;
        name: string;
    } | null;
};

// Interface para o objeto de data formatado que o Trait nos envia
export interface FormattedDate {
    default: string;
    short: string;
    short_with_time: string;
    human: string;
}

export interface ModelDefinition {
    name: string;
    version: string;
    attributes_count: number;
    relations_count: number;
    updated_at: string;
}

// Em resources/js/types/index.d.ts
// ...
export interface DashboardStats {
    totalMembers: number;
}

export interface PlatformSetting {
    id: number;
    platform_name: string;
    contact_info: { email?: string; whatsapp?: string };
    social_links: { instagram?: string; landing_page?: string };
    physical_address: string | null;
    logo_web_url: string | null;
    logo_mobile_url: string | null;
}
// Interface para o nosso modelo de Permissão
export interface Permission {
    id: number;
    name: string;
    created_at: FormattedDate | null;
    roles: Role[];
    roles_count: number;
}
export interface Role {
    id: number;
    name: string;
    created_at: FormattedDate | null;
    permissions: Permission[];
    permissions_count: number;
}

export interface Address {
    id: number;
    type: string;
    zip_code: string;
    street: string;
    number: string;
    complement: string;
    neighborhood: string;
    city: string;
    state: string;
    longitude: number | null;
    latitude: number | null;
}

export interface ScheduledNotifications {
    id: number;
    title: string;
    body: string;
    target_roles: string[];
    delivery_channels: string[];
    start_date: FormattedDate;
    period_in_days: number;
    is_active: boolean;
}

export interface Interaction {
    id: number;
    type:
        | "single_choice"
        | "multiple_choice"
        | "drag_and_drop"
        | "hotspot"
        | "auto";
    timestamp: number;
    data:
        | ChoiceInteractionData
        | DragDropInteractionData
        | AutoInteractionData
        | any; // O campo 'data' é um JSON flexível, então 'any' é um bom ponto de partida
}

// NOVA: Interface para um Vídeo
export interface Video {
    id: number;
    url: string;
    title: string | null;
    position?: {
        x: number;
        y: number;
    };
    interactions: Interaction[];
}

export interface Answer {
    text: string;
    is_correct: boolean;
    // Permite todas as ações possíveis
    action: "continue" | "jump_to_time" | "jump_to_video";
    // Permite que o target seja um número ou nulo
    target: number | null;
}

export interface AutoInteractionData {
    action: "jump_to_time" | "jump_to_video";
    target: number | null;
}

// Define a estrutura para os dados de uma interação de múltipla escolha
export interface ChoiceInteractionData {
    question: string;
    answers: Answer[];
    loop: {
        enabled: boolean;
        start_time: number;
    };
    image_url?: string | null;
    thumbnail_url?: string | null;
}

export interface DragDropInteractionData {
    question: string;
    draggables: { id: string; text: string }[];
    dropzones: { id: string; label: string; correct_item_id: string | null }[];

    image_url?: string | null;
    thumbnail_url?: string | null;
}
// Define a "forma" do nosso formulário de interação
export interface InteractionForm {
    type:
        | "single_choice"
        | "multiple_choice"
        | "drag_and_drop"
        | "hotspot"
        | "auto";
    timestamp: number;
    payload:
        | ChoiceInteractionData
        | DragDropInteractionData
        | AutoInteractionData;
    graphic: File | null;
}

export interface Project {
    id: number;
    title: string;
    type: "single_video" | "flow_video";
    status: string;
    rejection_feedback?: string | null;
    created_at: FormattedDate;
    updated_at: FormattedDate;
    deleted_at: FormattedDate | null;

    user: User | null;
    videos: Video[] | null;
    publication?: {
        state: string;
        active: boolean;
        can_publish: boolean;
        can_revoke: boolean;
        republish_ready: boolean;
        public_url?: string;
    };
}
export interface QueryParams {
    sort?: string;
    filter?: Record<string, any>;
    per_page?: string;
}
// Interface GENÉRICA para a resposta de paginação do Laravel.
// Podemos reutilizar isso para Projetos, Usuários, etc.
export interface Paginated<T> {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        path: string;
        per_page: number;
        to: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
}

export interface NavItem {
    title: string;
    href?: string;
    icon?: any; // Lucide Icon Component
    active?: boolean; // Lógica para manter aberto
    children?: NavItem[];
}

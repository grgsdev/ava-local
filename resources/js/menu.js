// resources/js/menu.js

import {
    LayoutDashboard,
    ShieldCheck,
    UserCog,
    KeyRound,
    Wrench,
    Newspaper,
    MessageSquare,
    Ticket,
    ShieldAlert,
    LayoutList,
    BookOpen,
    Building2,
} from "lucide-vue-next";

export const menu = [
    {
        name: "Dashboard",
        icon: LayoutDashboard,
        route: "dashboard",
        active: "dashboard",
    },
    {
        name: "Parâmetros de Sistema", // Vamos traduzir isso em seguida
        icon: Wrench,
        permission: "manage system", // Permissão que criamos no Seeder
        children: [
            {
                name: "Permissões", // Para traduzir: 'menu.permissions'
                icon: KeyRound,
                route: "system.permissions.index",
                active: "system.permissions.*",
                permission: "manage permissions",
            },
            {
                name: "Papéis", // Para traduzir: 'menu.roles'
                icon: ShieldCheck,
                route: "system.roles.index",
                active: "system.roles.*",
                permission: "manage roles",
            },
            {
                name: "Usuários de Sistema", // Para traduzir: 'menu.system_users'
                icon: UserCog,
                route: "system.users.index",
                active: "system.users.*",
                permission: "view system_developers",
            },
            {
                name: "Ações de Usuários", // Para traduzir: 'menu.system_users'
                icon: UserCog,
                route: "system.activity-log.index",
                active: "system.activity-log.*",
                permission: "manage users logs",
            },
            {
                name: "Command Logs", // Para traduzir: 'menu.system_users'
                icon: UserCog,
                route: "system.command-log.index",
                active: "system.command-log.*",
                permission: "manage command logs",
            },
            {
                name: "Registros Excluídos", // Para traduzir: 'menu.system_users'
                icon: UserCog,
                route: "system.deleted-records.index",
                active: "system.deleted-records.*",
                permission: "view system_developers",
            },
            {
                name: "Logs do Sistema", // Para traduzir: 'menu.system_users'
                icon: UserCog,
                route: "system.logs.index",
                active: "system.logs.*",
                permission: "manage logs",
            },
            {
                name: "Generator", // Para traduzir: 'menu.system_users'
                icon: UserCog,
                route: "system.generator.index",
                active: "system.generator.*",
                permission: "view system_developers",
            },
        ],
    },
    // {
    //     name: "Moderação",
    //     icon: ShieldCheck,
    //     permission: "manage logs",
    //     children: [
    //         {
    //             name: "Palavras Censuradas",
    //             icon: LayoutList,
    //             route: "admin.censored-words.index",
    //             active: "admin.censored-words.*",
    //         },
    //         {
    //             name: "Fila de Moderação",
    //             icon: ShieldAlert,
    //             route: "admin.moderation.index",
    //             active: "admin.moderation.*",
    //         },
    //     ],
    // },
    {
        name: "Plataforma",
        icon: LayoutDashboard,
        route: "admin.settings.index",
        active: "admin.settings.*",
        permission: "manage settings",
    },
    {
        name: "Empresas",
        icon: Building2,
        route: "admin.companies.index",
        active: "admin.companies.*",
        masterOnly: true,
    },
    {
        name: "Equipe",
        icon: LayoutDashboard,
        route: "team.index",
        active: "team.index",
        companyPermission: "manage team",
    },
    // {
    //     name: "Projetos OLD",
    //     icon: LayoutDashboard,
    //     route: "projects.index",
    //     active: "projects.index",
    //     permission: "manage projects",
    // },
    {
        name: "Projetos",
        icon: LayoutDashboard,
        route: "admin.courses.index",
        active: "admin.courses.*",
        masterOrCompanyPermission: "manage projects",
    },
    {
        name: "Enviar projeto",
        icon: LayoutDashboard,
        route: "projects.submit.index",
        active: "projects.submit.*",
        companyPermission: "edit projects",
    },
    {
        name: "Fila de aprovação",
        icon: LayoutDashboard,
        route: "projects.pending",
        active: "projects.pending",
        companyPermission: "approve projects",
    },

    {
        name: "Usuários",
        icon: Newspaper,
        permission: "manage users",
        children: [
            {
                name: "Usuários Geral",
                icon: Newspaper,
                route: "admin.users.index",
                active: "admin.users.*",
                permission: "manage users",
            },
        ],
    },

    {
        name: "Notificações",
        icon: MessageSquare,
        route: "admin.scheduled-notifications.index",
        active: "admin.scheduled-notifications.*",
        permission: "manage notifications", // Reutilize uma permissão de admin existente
    },
];

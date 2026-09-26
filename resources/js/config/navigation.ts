import { Clock, PenLine, User } from "lucide-vue-next";
import type { Component } from "vue";

export interface NavItem {
    icon: Component;
    label: string;
    /** Named Laravel route; items without one are not links yet. */
    routeName?: string;
}

export const NAV_ITEMS: NavItem[] = [
    { icon: User, label: "Account", routeName: "login" },
    { icon: Clock, label: "Arguments (Coming Soon)" },
    { icon: PenLine, label: "Submit a Quote" },
];

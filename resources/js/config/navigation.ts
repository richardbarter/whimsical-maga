import { Clock, PenLine, User } from "lucide-vue-next";
import type { Component } from "vue";

export interface NavItem {
    icon: Component;
    label: string;
    href?: string;
}

export const NAV_ITEMS: NavItem[] = [
    { icon: User, label: "Account", href: "/login" },
    { icon: Clock, label: "Arguments (Coming Soon)" },
    { icon: PenLine, label: "Submit a Quote" },
];

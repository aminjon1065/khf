import React from 'react';

/**
 * Official State Flag of the Republic of Tajikistan (Resolution №499).
 * Ratios: 2:3:2 (Red, White, Green) stripes, with a golden crown and seven stars arc in the center.
 */
export function TajikistanFlag({ className = 'w-12 h-6' }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 14 7"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={`border border-border shadow-xs ${className}`}
            aria-hidden="true"
        >
            {/* Red stripe */}
            <rect width="14" height="2" fill="#DA1A32" />
            {/* White stripe */}
            <rect y="2" width="14" height="3" fill="#FFFFFF" />
            {/* Green stripe */}
            <rect y="5" width="14" height="2" fill="#009743" />

            {/* Gold Crown and Stars group */}
            <g fill="#F8C300" transform="translate(7, 3.5) scale(0.011)">
                {/* Crown Base */}
                <path d="M-45 35 L45 35 L33 5 L-33 5 Z" />
                <rect x="-25" y="-1" width="50" height="5" />
                
                {/* Crown Arches */}
                <path d="M-45 5 C-45 -35, -15 -65, 0 -65 C15 -65, 45 -35, 45 5 C40 -10, 25 -20, 10 -20 C-10 -20, -10 5, -10 5 C-10 5, -10 -20, -30 -20 C-40 -20, -42 -10, -45 5 Z" />
                <circle cx="0" cy="-75" r="8" />

                {/* 7 Stars in an arc */}
                {/* Top star */}
                <path d="M0 -125 L3 -116 L12 -116 L5 -110 L8 -101 L0 -107 L-8 -101 L-5 -110 L-12 -116 L-3 -116 Z" />
                {/* Left stars */}
                <g transform="rotate(-30)">
                    <path d="M0 -125 L3 -116 L12 -116 L5 -110 L8 -101 L0 -107 L-8 -101 L-5 -110 L-12 -116 L-3 -116 Z" />
                </g>
                <g transform="rotate(-60)">
                    <path d="M0 -125 L3 -116 L12 -116 L5 -110 L8 -101 L0 -107 L-8 -101 L-5 -110 L-12 -116 L-3 -116 Z" />
                </g>
                <g transform="rotate(-90)">
                    <path d="M0 -125 L3 -116 L12 -116 L5 -110 L8 -101 L0 -107 L-8 -101 L-5 -110 L-12 -116 L-3 -116 Z" />
                </g>
                {/* Right stars */}
                <g transform="rotate(30)">
                    <path d="M0 -125 L3 -116 L12 -116 L5 -110 L8 -101 L0 -107 L-8 -101 L-5 -110 L-12 -116 L-3 -116 Z" />
                </g>
                <g transform="rotate(60)">
                    <path d="M0 -125 L3 -116 L12 -116 L5 -110 L8 -101 L0 -107 L-8 -101 L-5 -110 L-12 -116 L-3 -116 Z" />
                </g>
                <g transform="rotate(90)">
                    <path d="M0 -125 L3 -116 L12 -116 L5 -110 L8 -101 L0 -107 L-8 -101 L-5 -110 L-12 -116 L-3 -116 Z" />
                </g>
            </g>
        </svg>
    );
}

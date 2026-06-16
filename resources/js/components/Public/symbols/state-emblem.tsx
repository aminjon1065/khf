import React from 'react';

/**
 * Stylized gold-themed SVG representing the State Emblem (Coat of Arms) of the Republic of Tajikistan (Resolution №500).
 * Features rising sun behind mountains, crown with seven stars, and flanking wheat/cotton branches wrapped in flag ribbon.
 */
export function TajikistanEmblem({ className = 'size-9' }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 100 100"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={className}
            aria-hidden="true"
        >
            {/* Outer golden circular wreath representing wheat and cotton */}
            <circle cx="50" cy="50" r="46" stroke="#F8C300" strokeWidth="2.5" strokeDasharray="3 2" />
            <circle cx="50" cy="50" r="42" stroke="#F8C300" strokeWidth="1" />
            
            {/* Sun rays rising from the bottom */}
            <g stroke="#F8C300" strokeWidth="1.5">
                <line x1="50" y1="80" x2="50" y2="38" />
                <line x1="50" y1="80" x2="35" y2="43" />
                <line x1="50" y1="80" x2="65" y2="43" />
                <line x1="50" y1="80" x2="23" y2="54" />
                <line x1="50" y1="80" x2="77" y2="54" />
                <line x1="50" y1="80" x2="18" y2="70" />
                <line x1="50" y1="80" x2="82" y2="70" />
            </g>
            
            {/* Mountains at base of rays */}
            <path
                d="M32 75 L45 62 L52 67 L68 53 L78 72 L72 75 Z"
                fill="#009743"
                stroke="#F8C300"
                strokeWidth="1.5"
                strokeLinejoin="round"
            />
            
            {/* Rising Sun Disk */}
            <circle cx="50" cy="62" r="8" fill="#DA1A32" stroke="#F8C300" strokeWidth="1" />
            
            {/* Crown in center */}
            <g fill="#F8C300" stroke="#F8C300" strokeWidth="0.5">
                {/* Crown Base */}
                <path d="M42 45 L58 45 L56 49 L44 49 Z" />
                {/* Crown Arches */}
                <path d="M42 45 C42 40, 45 37, 50 37 C55 37, 58 40, 58 45 C57 43.5, 55 42.5, 53 42.5 C50 42.5, 50 45, 50 45 C50 45, 50 42.5, 47 42.5 C45 42.5, 43 43.5, 42 45 Z" />
                {/* Crown top ball */}
                <circle cx="50" cy="36" r="1" />
            </g>
            
            {/* 7 Stars in an arc above the crown */}
            <g fill="#F8C300">
                <circle cx="33.5" cy="39" r="1.2" />
                <circle cx="36.5" cy="32.5" r="1.2" />
                <circle cx="42.5" cy="28" r="1.2" />
                <circle cx="50" cy="26" r="1.2" />
                <circle cx="57.5" cy="28" r="1.2" />
                <circle cx="63.5" cy="32.5" r="1.2" />
                <circle cx="66.5" cy="39" r="1.2" />
            </g>
            
            {/* Open Book at the base */}
            <path
                d="M36 82 C42 80, 48 81, 50 83 C52 81, 58 80, 64 82 L64 77 C58 75, 52 76, 50 78 C48 76, 42 75, 36 77 Z"
                fill="#FFFFFF"
                stroke="#F8C300"
                strokeWidth="1"
            />
            <line x1="50" y1="78" x2="50" y2="83" stroke="#F8C300" strokeWidth="1" />
        </svg>
    );
}

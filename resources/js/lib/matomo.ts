export type MatomoGoals = Record<string, number>;

export type MatomoSharedProps = {
    enabled: boolean;
    goals: MatomoGoals;
};

/**
 * Fire a Matomo conversion goal (ТЗ §15.2). When a goal ID is not configured, falls back to a
 * `trackEvent` so local/staging installs still get signal without Matomo admin setup.
 */
export function trackMatomoGoal(
    goalKey: string,
    goals: MatomoGoals = {},
): void {
    if (!window._paq) {
        return;
    }

    const goalId = goals[goalKey];

    if (goalId !== undefined) {
        window._paq.push(['trackGoal', goalId]);

        return;
    }

    window._paq.push(['trackEvent', 'Conversion', goalKey]);
}

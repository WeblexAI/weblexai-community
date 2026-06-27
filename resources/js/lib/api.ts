import { routeWithProject } from '@/lib/helpers';
import { ActivityI, CollaboratorWithPivotI, LanguageI, ProjectI } from '@/types';

export const getLanguages = async (project: ProjectI | null = null): Promise<LanguageI[]> => {
    const data = {
        languages: [],
    };
    const url = route('languages.index') + '/' + (project?.id ?? '');
    const request = await fetch(url);
    const response = await request.json();
    if (response.success) {
        data.languages = response.data.languages;
    }
    return data.languages;
};

export const overviewGetCollaborators = async () => {
    let data: {
        members: CollaboratorWithPivotI[];
    } = { members: [] };

    const request = await fetch(routeWithProject('projects.overview.members'));
    const response = await request.json();

    if (response.success) {
        data = response.data;
    }
    return data;
};

export const overviewGetProjectDetails = async () => {
    let data: {
        languagesCount: number;
        translationsCount: number;
        translatedWordsCount: number;
        manualTranslationsCount: number;
        glossariesCount: number;
        excludedBlocksCount: number;
        blacklistedPagesCount: number;
    } = {
        languagesCount: 0,
        translationsCount: 0,
        translatedWordsCount: 0,
        manualTranslationsCount: 0,
        glossariesCount: 0,
        excludedBlocksCount: 0,
        blacklistedPagesCount: 0,
    };

    const request = await fetch(routeWithProject('projects.overview.project-details'));
    const response = await request.json();

    if (response.success) {
        data = response.data;
    }
    return data;
};

export const overviewGetActivities = async () => {
    let data: {
        activities: ActivityI[];
    } = { activities: [] };

    const request = await fetch(routeWithProject('projects.overview.activities'));
    const response = await request.json();

    if (response.success) {
        data = response.data;
    }
    return data;
};

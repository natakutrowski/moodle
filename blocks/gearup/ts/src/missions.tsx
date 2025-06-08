import React, { useMemo, useState } from 'react';
import ReactDOM from 'react-dom';
import { AchievementIcon, ChallengeIcon, QuestIcon, RecruitsIcon } from './components/Icons';
import Str from './components/Str';
import { ZeroStateBlock } from './components/ZeroStates';
import { useAnchorButtonProps, useStrings } from './lib/hooks';
import { makeDependenciesDefinition } from './lib/moodle';
import { classNames } from './lib/utils';
import { InputCheckbox } from './components/Inputs';

type Mission = {
    id: number;
    type: {
        isachievement: boolean;
        isquest: boolean;
        ischallenge: boolean;
    };
    title: string;
    visual?: {
        alt: string;
        id: string;
        url: string;
    };

    assignmentbehaviour?: {
        name: string;
        description: string;
        iscompulsory: boolean;
        isoptional: boolean;
        isdiscoverable: boolean;
    };

    timing?: {
        timelimitformatted: string;
        hastimelimit: boolean;
        isrepeating: boolean;
        repeats?: {
            daily: boolean;
            weekly: boolean;
            fortnightly: boolean;
            monthly: boolean;
        };
    };

    isarchived?: boolean;
    manageurl?: string;
    recruitcount?: number;
};

type ExternalProps = {
    missions: Mission[];
    createachievementurl: string;
    createchallengeurl?: string;
    createquesturl: string;
    usechallenges?: boolean;
};
type AppProps = ExternalProps;

const Pill = ({ onClick, children, active }: { onClick: () => void; children: React.ReactNode; active?: boolean }) => {
    const props = useAnchorButtonProps(onClick);
    return (
        <a
            className={classNames(
                'gu-px-2 gu-py-1 gu-rounded gu-no-underline hover:gu-no-underline',
                !active
                    ? 'gu-bg-gray-200 gu-text-inherit hover:gu-text-inherit hover:gu-bg-gray-100'
                    : 'gu-bg-blue-600 gu-text-white hover:gu-text-white'
            )}
            {...props}
        >
            {children}
        </a>
    );
};

const App: React.FC<AppProps> = ({ missions, usechallenges, createchallengeurl, createachievementurl, createquesturl }) => {
    const getStr = useStrings(['nrecruits']);
    const [filter, setFilter] = useState<string | null>(null);

    const [showArchived, setShowArchived] = useState<boolean>(false);
    const hasArchived = useMemo(() => missions.some((m) => m.isarchived), [missions]);

    const missionsInType = useMemo(
        () =>
            missions.filter((m) => {
                if (m.type.ischallenge && !usechallenges) {
                    return false;
                }

                if (filter === null) {
                    return true;
                } else if (filter === 'quests') {
                    return m.type.isquest;
                } else if (filter === 'achievements') {
                    return m.type.isachievement;
                } else if (filter === 'challenges') {
                    return m.type.ischallenge;
                }
                return false;
            }),
        [missions, filter, showArchived]
    );

    const finalMissions = useMemo(() => {
        return missionsInType.filter((m) => (showArchived ? true : !m.isarchived));
    }, [missionsInType, showArchived]);

    const nArchivedInType = useMemo(() => missionsInType.filter((m) => m.isarchived).length, [missionsInType]);

    if (!missions.length) {
        return (
            <ZeroStateBlock title={<Str id="nothinghereyet" />} subtitle={<Str id="getstartednewitem" />}>
                <div className="gu-divide-y gu-divide-gray-200 gu-mt-4">
                    <a
                        href={createquesturl}
                        className="gu-p-4 gu-flex gu-flex-row gu-text-current gu-no-underline hover:gu-bg-gray-100"
                    >
                        <div className="gu-shrink-0 gu-w-12 gu-mr-4">
                            <QuestIcon />
                        </div>
                        <div className="gu-grow">
                            <div className="gu-text-xl gu-text-medium">
                                <Str id="quest" />
                            </div>
                            <div className="gu-max-w-md gu-text-gray-500">
                                <Str id="questexplaination" />
                            </div>
                        </div>
                    </a>

                    <a
                        href={createachievementurl}
                        className="gu-p-4 gu-flex gu-flex-row gu-text-current gu-no-underline hover:gu-bg-gray-100"
                    >
                        <div className="gu-shrink-0 gu-w-12 gu-mr-4">
                            <AchievementIcon />
                        </div>
                        <div className="gu-grow">
                            <div className="gu-text-xl gu-text-medium">
                                <Str id="achievement" />
                            </div>
                            <div className="gu-max-w-md gu-text-gray-500">
                                <Str id="achievementexplaination" />
                            </div>
                        </div>
                    </a>

                    {usechallenges && createchallengeurl ? (
                        <a
                            href={createchallengeurl}
                            className="gu-p-4 gu-flex gu-flex-row gu-text-current gu-no-underline hover:gu-bg-gray-100"
                        >
                            <div className="gu-shrink-0 gu-w-12 gu-mr-4">
                                <ChallengeIcon />
                            </div>
                            <div className="gu-grow">
                                <div className="gu-text-xl gu-text-medium">
                                    <Str id="challenge" />
                                </div>
                                <div className="gu-max-w-md gu-text-gray-500">
                                    <Str id="challengeexplaination" />
                                </div>
                            </div>
                        </a>
                    ) : null}
                </div>
            </ZeroStateBlock>
        );
    }

    return (
        <>
            <div className="gu-flex gu-gap-2 gu-my-4">
                <div className="gu-grow gu-flex gu-gap-2">
                    <Pill onClick={() => setFilter(null)} active={filter === null}>
                        <Str id="all" />
                    </Pill>
                    <Pill onClick={() => setFilter('quests')} active={filter === 'quests'}>
                        <Str id="quests" />
                    </Pill>
                    <Pill onClick={() => setFilter('achievements')} active={filter === 'achievements'}>
                        <Str id="achievements" />
                    </Pill>
                    {usechallenges ? (
                        <Pill onClick={() => setFilter('challenges')} active={filter === 'challenges'}>
                            <Str id="challenges" />
                        </Pill>
                    ) : null}
                </div>
                {hasArchived ? (
                    <div>
                        <InputCheckbox
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                                setShowArchived(!showArchived);
                            }}
                            checked={showArchived}
                        >
                            <Str id="showarchivedn" a={nArchivedInType} />
                        </InputCheckbox>
                    </div>
                ) : null}
            </div>
            {filter && !finalMissions.length ? (
                <ZeroStateBlock title={<Str id="nonehereyet" />} subtitle={<Str id="getstartednewone" />}></ZeroStateBlock>
            ) : (
                <div className="gu-flex gu-flex-col gu-divide-y gu-divide-gray-200">
                    {finalMissions.map((mission) => {
                        return (
                            <div key={mission.id}>
                                <a
                                    className="gu-rounded gu-p-2 gu-flex gu-gap-2 hover:gu-bg-gray-200 gu-transition-colors gu-text-inherit gu-no-underline hover:gu-no-underline hover:gu-text-inherit"
                                    href={mission.manageurl}
                                >
                                    <div className="gu-h-12 gu-w-12 gu-shrink-0">
                                        <MissionVisual mission={mission} />
                                    </div>
                                    <div className="gu-grow gu-flex gu-flex-col">
                                        <div className="gu-flex gu-grow gu-gap-2">
                                            <div className="gu-font-medium gu-grow gu-text-lg gu-leading-tight">
                                                {mission.title}
                                            </div>
                                            <div>
                                                {mission.isarchived ? (
                                                    <span className="badge gu-badge-secondary">
                                                        <Str id="archived" />
                                                    </span>
                                                ) : null}
                                            </div>
                                        </div>
                                        <div className="gu-text-xs gu-text-gray-500 gu-flex gu-items-center gu-leading-none">
                                            <div className="gu-flex gu-items-center gu-divide-x gu-grow">
                                                <div className="gu-shrink-0 gu-flex gu-gap-1 gu-items-center gu-pr-1">
                                                    <div className="gu-w-4 gu-h-4 gu-shrink-0">
                                                        <MissionIcon mission={mission} />
                                                    </div>
                                                    <div>
                                                        {mission.type.isachievement ? <Str id="achievement" /> : null}
                                                        {mission.type.isquest ? <Str id="quest" /> : null}
                                                        {mission.type.ischallenge ? <Str id="challenge" /> : null}
                                                    </div>
                                                </div>
                                                <div className="gu-pl-1">
                                                    {mission.type.ischallenge && mission.timing?.isrepeating ? (
                                                        <>
                                                            {mission.timing.repeats?.daily ? <Str id="daily" /> : null}
                                                            {mission.timing.repeats?.weekly ? <Str id="weekly" /> : null}
                                                            {mission.timing.repeats?.fortnightly ? <Str id="fortnightly" /> : null}
                                                            {mission.timing.repeats?.monthly ? <Str id="monthly" /> : null}
                                                        </>
                                                    ) : null}
                                                    {mission.type.isquest ? (
                                                        <>
                                                            {mission.assignmentbehaviour?.isoptional ? (
                                                                <Str id="optionalquest" />
                                                            ) : null}
                                                            {mission.assignmentbehaviour?.iscompulsory ? (
                                                                <Str id="compulsoryquest" />
                                                            ) : null}
                                                            {mission.assignmentbehaviour?.isdiscoverable ? (
                                                                <Str id="discoverablequest" />
                                                            ) : null}
                                                        </>
                                                    ) : null}
                                                </div>
                                            </div>
                                            <div className="gu-shrink-0 gu-flex gu-items-center">
                                                <div
                                                    className="gu-shrink-0 gu-flex gu-gap-1 gu-items-center"
                                                    title={getStr('nrecruits', mission.recruitcount ?? 0)}
                                                >
                                                    <div>{mission.recruitcount ?? 0}</div>
                                                    <div className="gu-w-4 gu-h-4 gu-shrink-0">
                                                        <RecruitsIcon />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        );
                    })}
                </div>
            )}
        </>
    );
};

const MissionIcon = ({ mission }: { mission: Mission }) => {
    if (mission.type.isachievement) {
        return <AchievementIcon />;
    } else if (mission.type.ischallenge) {
        return <ChallengeIcon />;
    }
    return <QuestIcon />;
};

const MissionVisual = ({ mission }: { mission: Mission }) => {
    if (mission.type.isachievement) {
        return <AchievementBadge visual={mission.visual} />;
    } else if (mission.type.ischallenge) {
        return <ChallengeIcon />;
    }
    return <QuestNarrator visual={mission.visual} />;
};

const AchievementBadge = ({ visual }: { visual: Mission['visual'] }) => {
    if (!visual) {
        return (
            <div className="gu-flex gu-items-center gu-justify-center gu-h-full gu-w-full">
                <svg className="gu-w-full gu-min-w-[1rem]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 528 528">
                    <rect width="528" height="528" rx="64.54" style={{ fill: '#333' }} />
                    <path
                        d="M463.46,28A36.58,36.58,0,0,1,500,64.54V463.46A36.58,36.58,0,0,1,463.46,500H64.54A36.58,36.58,0,0,1,28,463.46V64.54A36.58,36.58,0,0,1,64.54,28H463.46m0-28H64.54A64.53,64.53,0,0,0,0,64.54V463.46A64.53,64.53,0,0,0,64.54,528H463.46A64.53,64.53,0,0,0,528,463.46V64.54A64.53,64.53,0,0,0,463.46,0Z"
                        style={{ fill: '#666' }}
                    />
                    <polygon
                        points="107.25 221.37 156.73 134.7 371.27 134.7 420.75 221.37 264 406.66 107.25 221.37"
                        style={{ fill: 'gray' }}
                    />
                    <path
                        d="M368.47,139.51l46.43,81.33L264,399.2,113.1,220.84l46.43-81.33H368.47m5.59-9.63H153.94l-52.55,92L264,414.12,426.61,221.91l-52.55-92Z"
                        style={{ fill: '#333' }}
                    />
                    <polygon
                        points="327.12 221.91 264 407.12 202.21 221.91 327.12 221.91"
                        style={{ fill: 'none', stroke: '#333', strokeLinejoin: 'round', strokeWidth: '9.635276198387146px' }}
                    />
                    <polygon
                        points="264 129.88 202.21 221.91 327.12 221.91 264 129.88"
                        style={{ fill: 'none', stroke: '#333', strokeLinejoin: 'round', strokeWidth: '9.635276198387146px' }}
                    />
                    <polygon
                        points="374.06 129.88 372.63 129.88 327.12 221.91 426.61 221.91 374.06 129.88"
                        style={{ fill: 'none', stroke: '#333', strokeLinejoin: 'round', strokeWidth: '9.635276198387146px' }}
                    />
                    <polygon
                        points="156.71 129.88 153.94 129.88 101.39 221.91 202.21 221.91 156.71 129.88"
                        style={{ fill: 'none', stroke: '#333', strokeLinejoin: 'round', strokeWidth: '9.635276198387146px' }}
                    />
                </svg>
            </div>
        );
    }
    return (
        <img src={visual.url} alt={visual.alt} className="gu-h-full gu-w-full gu-max-h-full gu-rounded gu-pointer-events-none" />
    );
};

const QuestNarrator = ({ visual }: { visual: Mission['visual'] }) => {
    if (!visual) {
        return (
            <div className="gu-flex gu-items-center gu-text-3xl gu-justify-center gu-h-full gu-w-full gu-bg-gray-200 gu-p-1 gu-rounded">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth="1.5"
                    stroke="currentColor"
                    className="gu-w-full gu-max-h-full"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                    />
                </svg>
            </div>
        );
    }
    return (
        <img src={visual.url} alt={visual.alt} className="gu-h-full gu-w-full gu-max-h-full gu-rounded gu-pointer-events-none" />
    );
};

async function startModalApp(modal: any, node: HTMLElement, props: ExternalProps) {
    console.error('block_gearup/react-missions does not implement startModalApp.');
}

async function startApp(node: HTMLElement, props: ExternalProps) {
    ReactDOM.render(<App {...props} />, node);
}

const dependencies = makeDependenciesDefinition(['core/str']);

export { dependencies, startApp, startModalApp };

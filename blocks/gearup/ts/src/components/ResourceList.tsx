import React, { ReactComponentElement, ReactNode, useMemo, useState } from 'react';
import { useRoleButtonListeners, useString, useUniqueId } from '../lib/hooks';
import { Resource } from '../lib/types';
import { classNames } from '../lib/utils';
import { Button } from './Buttons';
import { InputCheckbox } from './Inputs';
import Str from './Str';

type ResourceListProps = { resources: Resource[]; onSelect?: (r: Resource) => void };

const ListEntry: React.FC<{
    resource: Resource;
    onSelect: () => void;
}> = ({ resource, onSelect }) => {
    const { label, description, isavailable = true } = resource;

    const headingId = useUniqueId();
    const buttonListeners = useRoleButtonListeners(onSelect);

    const disabledOpacityClass = `${!isavailable ? 'gu-opacity-60 group-focus:gu-opacity-100 group-hover:gu-opacity-100' : ''}`;

    return (
        <div className="gu-p-[0.2rem] gu-relative gu-group focus:gu-z-10 hover:gu-bg-gray-100">
            <div tabIndex={0} role="button" aria-describedby={headingId} className="gu-px-1.5 gu-py-0.5" {...buttonListeners}>
                <div id={headingId} className={`gu-flex`}>
                    <div className={classNames(disabledOpacityClass, 'gu-text-xl gu-text-medium')}>{label}</div>
                    {!isavailable ? (
                        <div className="gu-ml-2">
                            <span className="badge gu-badge-pill gu-badge-warning">
                                <Str id="unavailable" />
                            </span>
                        </div>
                    ) : null}
                </div>
                <div
                    className={classNames(disabledOpacityClass, 'gu-text-gray-500')}
                    dangerouslySetInnerHTML={{ __html: description }}
                />
            </div>
        </div>
    );
};

const Container: React.FC = ({ children }) => {
    return <div className="gu-min-h-full gu-max-h-full gu-flex gu-flex-col">{children}</div>;
};

const EmptyResult: React.FC<{
    message?: ReactNode;
    content?: ReactNode;
}> = ({ message, content }) => {
    return (
        <div className="gu-flex-1 gu-flex gu-flex-col gu-items-center gu-justify-center gu-text-center">
            <div>{message || <Str id="noneareavailable" />}</div>
            {content ? <div className="gu-my-2">{content}</div> : null}
        </div>
    );
};

const PlainResourceList: React.FC<ResourceListProps & { emptyContent?: ReactNode }> = ({ resources, onSelect, emptyContent }) => {
    return (
        <div className="gu-flex-1 gu-min-h-full gu-max-h-full gu-flex gu-flex-col">
            <div className="gu-max-h-full gu-overflow-y-auto gu-flex gu-flex-1">
                {resources.length ? (
                    <div className="gu-flex-1 gu-divide-y gu-divide-gray-200">
                        {resources.map((o) => {
                            return <ListEntry key={o.name} resource={o} onSelect={() => onSelect && onSelect(o)} />;
                        })}
                    </div>
                ) : (
                    emptyContent || <EmptyResult />
                )}
            </div>
        </div>
    );
};

const ResourceListWithFilters: React.FC<ResourceListProps> = ({ resources, ...props }) => {
    const filterStr = useString('filterellipsis', 'block_gearup');

    const [stringFilter, setStringFilter] = useState('');
    const [filterUnavailable, setFilterUnavailable] = useState(true);
    const nUnavailable = useMemo(() => resources.filter((r) => !r.isavailable).length, [resources]);
    const hasUnavailable = nUnavailable > 0;

    const effectiveStringFilter = stringFilter.trim().toLowerCase();
    const hasStringFilter = Boolean(effectiveStringFilter);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => setStringFilter(e.target.value);

    let finalResources = resources;

    if (hasStringFilter) {
        finalResources = finalResources.filter((r) => {
            return (
                r.label.toLowerCase().indexOf(effectiveStringFilter) > -1 ||
                r.description.toLowerCase().indexOf(effectiveStringFilter) > -1
            );
        });
    }
    if (filterUnavailable) {
        finalResources = finalResources.filter((r) => r.isavailable);
    }

    return (
        <Container>
            <div className="gu-mb-2 gu-flex gu-flex-row gu-items-center">
                <div className="gu-flex-1">
                    <input
                        className="form-control gu-w-full"
                        type="text"
                        placeholder={filterStr}
                        onChange={handleChange}
                        value={stringFilter}
                    />
                </div>
                {hasUnavailable ? (
                    <div className="gu-ml-4 gu-pr-2">
                        <InputCheckbox
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setFilterUnavailable(!e.target.checked)}
                            checked={!filterUnavailable}
                        >
                            <Str id="showunavailablen" a={nUnavailable} />
                        </InputCheckbox>
                    </div>
                ) : null}
            </div>

            <PlainResourceList
                {...props}
                resources={finalResources}
                emptyContent={
                    <>
                        {hasStringFilter ? (
                            <EmptyResult
                                message={<Str id="nothingmatchesfilter" />}
                                content={
                                    <Button onClick={(e) => setStringFilter('')}>
                                        <Str id="clearfilter" />
                                    </Button>
                                }
                            />
                        ) : (
                            <EmptyResult
                                content={
                                    filterUnavailable && hasUnavailable ? (
                                        <Button onClick={(e) => setFilterUnavailable(false)}>
                                            <Str id="showunavailable" />
                                        </Button>
                                    ) : null
                                }
                            />
                        )}
                    </>
                }
            />
        </Container>
    );
};

const ResourceList: React.FC<ResourceListProps & { withFilters?: boolean }> = ({ withFilters, resources, ...props }) => {
    const sortedResources = useMemo(() => {
        return resources.sort((a, b) => {
            return a.label.localeCompare(b.label);
        });
    }, [resources]);
    return withFilters ? (
        <ResourceListWithFilters resources={sortedResources} {...props} />
    ) : (
        <PlainResourceList resources={sortedResources} {...props} />
    );
};

export default ResourceList;

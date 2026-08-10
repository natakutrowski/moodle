import React, { useRef, useState } from 'react';
import ReactDOM from 'react-dom';
import { LinkButton } from './components/Buttons';
import { DynamicFormLoader } from './components/DynamicForm';
import ResourceList from './components/ResourceList';
import Str from './components/Str';
import { getModule, makeDependenciesDefinition } from './lib/moodle';
import { Resource } from './lib/types';
import { classNames } from './lib/utils';

type ExternalProps = {
    resources?: Resource[];
    resourceExternalFunction?: string;
    resourceExternalFunctionArgs?: { [index: string]: string | number };
    formClass: string;
    formArgs?: { [index: string]: any };
    formResourceArgAlias?: string;
};
type AppProps = {
    resources: Resource[];
    formClass: string;
    formArgs?: { [index: string]: any };
    formResourceArgAlias?: string;
    onSelected?: (r?: any) => void;
    onFormLoaded?: (f: any) => void;
};

const App: React.FC<AppProps> = ({
    resources,
    onSelected,
    formClass,
    formArgs = {},
    formResourceArgAlias = 'resource',
    onFormLoaded,
}) => {
    const [selected, setSelected] = useState<Resource>();
    const listRef = useRef<HTMLDivElement>(null);
    const formRef = useRef<HTMLDivElement>(null);

    const handleSelect = (r?: Resource) => {
        const Aria = getModule('core/aria');
        const listDiv = listRef.current;
        const formDiv = formRef.current;

        setSelected(r);
        if (!r) {
            Aria.unhide(listDiv);
            listDiv?.focus();
            Aria.hide(formDiv);
        } else if (r) {
            Aria.unhide(formDiv);
            formDiv?.focus();
            Aria.hide(listDiv);
        }

        onSelected && onSelected(r);
    };

    const hasSelected = Boolean(selected);
    const classes = 'gu-absolute gu-inset-0 gu-transform-gpu gu-transition-transform gu-overflow-y-auto gu-duration-300';

    return (
        <div className="gu-w-full gu-h-full gu-overflow-hidden gu-relative">
            <div className={classNames(classes, hasSelected ? 'gu--translate-x-full' : '')} ref={listRef}>
                <ResourceList resources={resources} onSelect={handleSelect} withFilters />
            </div>
            <div className={classNames(classes, !hasSelected ? 'gu-translate-x-full' : '')} ref={formRef}>
                {selected ? (
                    <>
                        <div className="gu-text-sm">
                            {'< '}
                            <LinkButton onClick={() => handleSelect()}>
                                <Str id="chooseanother" />
                            </LinkButton>
                        </div>
                        <h3 className="gu-mt-1">{selected.label}</h3>
                        <div
                            className="gu-my-4 gu-mt-2 gu-bg-gray-100 gu-p-2"
                            dangerouslySetInnerHTML={{ __html: selected.description }}
                        />
                        {selected.isavailable ? (
                            <DynamicFormLoader
                                onFormLoaded={onFormLoaded}
                                formClass={formClass}
                                formArgs={{ ...formArgs, [formResourceArgAlias]: selected.name }}
                            />
                        ) : (
                            <div>
                                <p>
                                    <Str id="unavailablebecause" />
                                </p>
                                <ul>
                                    {selected.unavailablereasons?.map((r, i) => (
                                        <li key={i}>{r}</li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </>
                ) : null}
            </div>
        </div>
    );
};

async function openModal(modal: any, root: HTMLElement, props: ExternalProps, originatingNode?: HTMLElement) {
    const ModalEvents = getModule('core/modal_events');
    const saveButton = modal.getFooter().find(modal.getActionSelector('save'));
    let form: any;

    const handleSelected = (r: any) => {
        if (r) return; // Do nothing when we select a type.
        saveButton.attr('disabled', !r);
    };

    const handleFormLoaded = (f: any) => {
        form = f;
        saveButton.attr('disabled', false);
        form.addEventListener(form.events.SUBMIT_BUTTON_PRESSED, (e: any) => {
            saveButton.attr('disabled', true);
        });
        form.addEventListener(form.events.ERROR, (e: any) => {
            saveButton.attr('disabled', false);
        });
        form.addEventListener(form.events.CLIENT_VALIDATION_ERROR, (e: any) => {
            saveButton.attr('disabled', false);
        });
        form.addEventListener(form.events.SERVER_VALIDATION_ERROR, (e: any) => {
            saveButton.attr('disabled', false);
        });
        form.addEventListener(form.events.FORM_SUBMITTED, (e: any) => {
            e.preventDefault();
            // We must mark the form as submitted before we reload the page because in
            // some edge cases scenario the changechecker assumes that the form is still dirty.
            getModule('block_gearup/compat').markFormSubmitted(getModule('block_gearup/compat').getFormNode(form));

            const smartRefreshing = originatingNode
                ? getModule('block_gearup/refreshable').smartRefreshFromNode(originatingNode, true)
                : null;
            if (smartRefreshing) {
                modal.hide();
                return;
            }

            window.location.reload();
        });
    };

    saveButton.attr('disabled', true);
    modal.getRoot().on(ModalEvents.hidden, (e: Event) => {
        if (!form) return;
        form.notifyResetFormChanges();
    });
    modal.getRoot().on(ModalEvents.save, (e: Event) => {
        if (!form) return;
        e.preventDefault();

        // Manually trigger the submission.
        const node = getModule('block_gearup/compat').getFormNode(form);
        const submitter = document.createElement('input');
        submitter.type = 'submit';
        submitter.hidden = true;
        node.appendChild(submitter);
        submitter.click();
        node.removeChild(submitter);
    });

    const { resources, resourceExternalFunction, resourceExternalFunctionArgs, ...remainingProps } = props;
    let resourceLoader = Promise.resolve(resources || []);
    if (!resources && resourceExternalFunction) {
        resourceLoader = getModule('core/ajax').call([
            {
                methodname: resourceExternalFunction,
                args: resourceExternalFunctionArgs,
            },
        ])[0];
    }

    // Quick and dirty solution to load the resources async. Ideally we'd show a spinner while it loads.
    resourceLoader
        .then((resources) => {
            ReactDOM.render(
                <App {...remainingProps} resources={resources} onSelected={handleSelected} onFormLoaded={handleFormLoaded} />,
                root
            );
        })
        .catch(getModule('core/notification').exception);
}

async function startModalApp(modal: any, node: HTMLElement, props: ExternalProps, originatingNode?: HTMLElement) {
    openModal(modal, node, props, originatingNode);
}

async function startApp(node: HTMLElement, props: ExternalProps) {
    console.error('block_gearup/react-creator does not implement startApp.');
}

const dependencies = makeDependenciesDefinition([
    'block_gearup/compat',
    'block_gearup/refreshable',
    'block_gearup/utils',
    'core_form/dynamicform',
    'core/ajax',
    'core/aria',
    'core/modal_events',
    'core/notification',
    'core/str',
]);

export { dependencies, startApp, startModalApp };

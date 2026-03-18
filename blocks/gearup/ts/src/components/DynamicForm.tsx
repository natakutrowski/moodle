import React, { useEffect, useState } from 'react';
import { getModule } from '../lib/moodle';

export const DynamicFormLoader: React.FC<{ formClass: string; formArgs: any; onFormLoaded?: (f: any) => void }> = ({
    formClass,
    formArgs,
    onFormLoaded,
}) => {
    const id = `block_gearup-dynamic-form-loader-${Date.now()}`;
    const [state, setState] = useState<{ id: string; formClass: string; form: any }>();

    useEffect(() => {
        if (formClass === state?.formClass) {
            return;
        }

        const DynamicForm = getModule('core_form/dynamicform');
        const f = new DynamicForm(document.querySelector(`#${id}`), formClass);
        f.load(formArgs)
            .then(() => {
                onFormLoaded && onFormLoaded(f);
            })
            .catch(getModule('core/notification').exception);
        setState({
            id: id,
            form: f,
            formClass: formClass,
        });
    }, [formClass]);

    const effectiveId = !state?.id || state?.formClass !== formClass ? id : state.id;

    return <div className="gu-dynamic-form" id={effectiveId}></div>;
};

import React from 'react';
import { useUniqueId } from '../lib/hooks';
import { isBootstrap5 } from '../lib/moodle';

type InputCheckboxProps = {
    checked?: boolean;
    disabled?: boolean;
    onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
    children: React.ReactNode;
};

export const InputCheckbox = (props: InputCheckboxProps) => {
    const id = useUniqueId();
    const Component = isBootstrap5() ? InputCheckboxBs5 : InputCheckboxBs4;
    return <Component {...props} />;
};

const InputCheckboxBs4 = ({ checked, onChange, disabled, children }: InputCheckboxProps) => {
    const id = useUniqueId();
    return (
        <div className="custom-control custom-checkbox">
            <input
                type="checkbox"
                className="custom-control-input"
                id={id}
                checked={checked}
                onChange={onChange}
                disabled={disabled}
            />
            <label className="custom-control-label" htmlFor={id}>
                {children}
            </label>
        </div>
    );
};

const InputCheckboxBs5 = ({ checked, onChange, disabled, children }: InputCheckboxProps) => {
    const id = useUniqueId();
    return (
        <div className="form-check">
            <input type="checkbox" className="form-check-input" id={id} checked={checked} onChange={onChange} disabled={disabled} />
            <label className="form-check-label" htmlFor={id}>
                {children}
            </label>
        </div>
    );
};

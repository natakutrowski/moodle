import React from 'react';
import { useAnchorButtonProps } from '../lib/hooks';
import { classNames } from '../lib/utils';

export const Button: React.FC<{
    disabled?: boolean;
    primary?: boolean;
    onClick?: (e: React.MouseEvent<HTMLButtonElement>) => void;
}> = ({ onClick, disabled, primary, children }) => {
    return (
        <button
            className={classNames('btn', primary ? 'btn-primary' : 'btn-secondary')}
            onClick={onClick}
            disabled={disabled}
            type="button"
        >
            {children}
        </button>
    );
};

export const LinkButton: React.FC<{ onClick: () => void } & React.AnchorHTMLAttributes<HTMLAnchorElement>> = ({
    children,
    onClick,
    ...props
}) => {
    const anchorButtonProps = useAnchorButtonProps(onClick);
    return (
        <a {...props} {...anchorButtonProps}>
            {children}
        </a>
    );
};

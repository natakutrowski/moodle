import React from 'react';
import { useAnchorButtonProps } from '../lib/hooks';

const LinkButton: React.FC<{ onClick: () => void } & React.AnchorHTMLAttributes<HTMLAnchorElement>> = ({
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

export default LinkButton;

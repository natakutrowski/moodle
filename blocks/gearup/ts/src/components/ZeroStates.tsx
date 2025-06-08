import React from 'react';

export const ZeroStateBlock = ({
    icon,
    title,
    subtitle,
    children,
}: {
    icon?: React.ReactNode;
    title: React.ReactNode;
    subtitle: React.ReactNode;
    children?: React.ReactNode;
}) => {
    return (
        <div className="gu-grow gu-flex gu-flex-col gu-items-center gu-justify-center gu-border-dashed gu-border-gray-200 gu-border-2 gu-rounded-lg gu-p-4 gu-py-8">
            <div className="gu-text-center">
                {icon ? (
                    <div className="gu-w-20 gu-mx-auto">
                        <div className="gu-w-full gu-px-4">{icon}</div>
                    </div>
                ) : null}
                <h3 className="gu-my-2">{title}</h3>
                <p className="gu-m-0">{subtitle}</p>
            </div>
            {children}
        </div>
    );
};

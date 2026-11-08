/** Generated static JSX text replacements; review dynamic strings and attribute labels separately. */
import React, {createContext, useContext} from 'react';

type Content = Record<string, Record<string, string>>;
const ContentContext = createContext<Content>({});
export function BridgeTextProvider({content, children}: {content: Content; children: React.ReactNode}) {
  return <ContentContext.Provider value={content}>{children}</ContentContext.Provider>;
}
export function BridgeText({id, fallback}: {id: string; fallback: string}) {
  const content = useContext(ContentContext);
  const translated = content?.page?.[id];
  return <>{typeof translated === 'string' ? translated : fallback}</>;
}

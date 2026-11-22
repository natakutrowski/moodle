// Optional binaries. The Lovable export contains metadata JSON but NOT these files.
// When original binaries are added to src/assets, Vite includes them in the trusted bundle.
const covers = import.meta.glob('../assets/story-cover-*.webp', {eager:true,query:'?url',import:'default'}) as Record<string,string>;
// Audio fourni uniquement par Moodle File API, jamais dans le Design Pack.
export const storyCover = (day:number):string|undefined => covers[`../assets/story-cover-${day}.webp`];
export const previewAudio: string | undefined = undefined;

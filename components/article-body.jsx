import ReactMarkdown from 'react-markdown'; import remarkGfm from 'remark-gfm';
export default function ArticleBody({content}){if(!content)return <p className="text-muted-foreground text-center py-12">Content is being prepared.</p>;return <div className="article-prose"><ReactMarkdown remarkPlugins={[remarkGfm]}>{content}</ReactMarkdown></div>}

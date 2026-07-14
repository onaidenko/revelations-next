import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';

export default function ArticleBody({
  content,
  format = 'markdown',
}) {
  if (!content) {
    return (
      <p className="py-12 text-center text-muted-foreground">
        Content is being prepared.
      </p>
    );
  }

  if (format === 'html') {
    return (
      <div
        className="article-prose"
        dangerouslySetInnerHTML={{
          __html: content,
        }}
      />
    );
  }

  return (
    <div className="article-prose">
      <ReactMarkdown remarkPlugins={[remarkGfm]}>
        {content}
      </ReactMarkdown>
    </div>
  );
}

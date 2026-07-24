(function (wp) {
    'use strict';

    const registerPlugin = wp.plugins.registerPlugin;
    const el = wp.element.createElement;
    const Fragment = wp.element.Fragment;
    const components = wp.components;
    const data = wp.data;

    const editorData =
        window.revelationsCmsEditorData || {};

    const PluginDocumentSettingPanel =
        (
            wp.editPost &&
            wp.editPost.PluginDocumentSettingPanel
        ) ||
        (
            wp.editor &&
            wp.editor.PluginDocumentSettingPanel
        );

    if (!PluginDocumentSettingPanel) {
        return;
    }

    if (wp.data && wp.data.dispatch) {
        const editPost = wp.data.dispatch('core/edit-post');
        if (editPost && editPost.removeEditorPanel) {
            editPost.removeEditorPanel('taxonomy-panel-category');
        }
    }

    function normalizeText(value) {
        if (
            value &&
            typeof value === 'object'
        ) {
            if (
                Object.prototype.hasOwnProperty.call(
                    value,
                    'raw'
                )
            ) {
                value = value.raw;
            } else if (
                Object.prototype.hasOwnProperty.call(
                    value,
                    'rendered'
                )
            ) {
                value = value.rendered;
            }
        }

        return String(value || '')
            .replace(/\r\n/g, '\n')
            .replace(/\r/g, '\n');
    }

    function normalizeIds(values) {
        if (!Array.isArray(values)) {
            return [];
        }

        return values
            .map(function (value) {
                return Number(value) || 0;
            })
            .filter(function (value) {
                return value > 0;
            })
            .sort(function (left, right) {
                return left - right;
            });
    }

    function sameIds(left, right) {
        const normalizedLeft =
            normalizeIds(left);

        const normalizedRight =
            normalizeIds(right);

        return (
            JSON.stringify(normalizedLeft) ===
            JSON.stringify(normalizedRight)
        );
    }

    function publicSources(value) {
        try {
            const parsed = JSON.parse(normalizeText(value) || '[]');
            return Array.isArray(parsed)
                ? parsed.filter(function (source) {
                    return source && typeof source === 'object';
                }).map(function (source) {
                    return { label: normalizeText(source.label), url: normalizeText(source.url) };
                })
                : [];
        } catch {
            return [];
        }
    }

    function statusRow(item) {
        const ready = Boolean(item.ready);

        return el(
            'div',
            {
                key: item.key,
                style: {
                    display: 'flex',
                    alignItems: 'flex-start',
                    gap: '8px',
                    padding: '7px 0',
                    borderBottom:
                        '1px solid #e0e0e0',
                },
            },

            el(
                'span',
                {
                    'aria-hidden': true,
                    style: {
                        flex: '0 0 auto',
                        width: '18px',
                        fontWeight: '700',
                        color: ready
                            ? '#008a20'
                            : '#b32d2e',
                    },
                },
                ready ? '✓' : '!'
            ),

            el(
                'div',
                {
                    style: {
                        minWidth: 0,
                    },
                },

                el(
                    'div',
                    {
                        style: {
                            fontWeight: '600',
                        },
                    },
                    item.label
                ),

                el(
                    'div',
                    {
                        style: {
                            marginTop: '2px',
                            fontSize: '12px',
                            color: '#646970',
                        },
                    },
                    item.detail
                )
            )
        );
    }

    function RevelationsArticlePanel() {
        const state = data.useSelect(
            function (select) {
                const editor =
                    select('core/editor');

                return {
                    meta:
                        editor.getEditedPostAttribute(
                            'meta'
                        ) || {},

                    title:
                        editor.getEditedPostAttribute(
                            'title'
                        ),

                    content:
                        editor.getEditedPostAttribute(
                            'content'
                        ),

                    excerpt:
                        editor.getEditedPostAttribute(
                            'excerpt'
                        ),

                    categories:
                        editor.getEditedPostAttribute(
                            'categories'
                        ) || [],

                    tags:
                        editor.getEditedPostAttribute(
                            'tags'
                        ) || [],

                    featuredMedia:
                        Number(
                            editor.getEditedPostAttribute(
                                'featured_media'
                            )
                        ) || 0,
                };
            },
            []
        );

        const editor =
            data.useDispatch(
                'core/editor'
            );

        const meta = state.meta;
        const snapshot =
            editorData.reviewedSnapshot;

        function updateMeta(key, value) {
            editor.editPost({
                meta: Object.assign(
                    {},
                    meta,
                    {
                        [key]: value,
                    }
                ),
            });
        }

        let reviewedFieldsChanged = false;

        if (
            editorData.reviewIsCurrent &&
            snapshot
        ) {
            reviewedFieldsChanged =
                normalizeText(state.title) !==
                    normalizeText(snapshot.title) ||

                normalizeText(state.content) !==
                    normalizeText(snapshot.content) ||

                normalizeText(state.excerpt) !==
                    normalizeText(snapshot.excerpt) ||

                !sameIds(
                    state.categories,
                    snapshot.categories
                ) ||

                !sameIds(state.tags, snapshot.tags || []) ||

                normalizeText(meta.revelations_author) !==
                    normalizeText(snapshot.displayedAuthor) ||

                normalizeText(
                    meta.revelations_seo_title
                ) !==
                    normalizeText(
                        snapshot.seoTitle
                    ) ||

                normalizeText(
                    meta.revelations_seo_description
                ) !==
                    normalizeText(
                        snapshot.seoDescription
                    ) ||

                ['revelation', 'sourceNote', 'editorialNote', 'disclosure', 'publicSources'].some(function (key) {
                    const metaKey = {
                        revelation: 'revelations_revelation', sourceNote: 'revelations_source_note', editorialNote: 'revelations_editorial_note', disclosure: 'revelations_disclosure', publicSources: 'revelations_public_sources',
                    }[key];
                    return normalizeText(meta[metaKey]) !== normalizeText(snapshot[key]);
                });
        }

        let reviewReady = true;
        let reviewDetail = 'Not required';

        if (
            editorData.editorialReviewRequired
        ) {
            if (
                !editorData.reviewIsCurrent
            ) {
                reviewReady = false;
                reviewDetail =
                    'Editorial review required';
            } else if (
                reviewedFieldsChanged
            ) {
                reviewReady = false;
                reviewDetail =
                    'Save the draft and review the new version';
            } else {
                reviewReady = true;
                reviewDetail =
                    'Current reviewed version';
            }
        }

        const readinessItems = [
            {
                key: 'featured-image',
                label: 'Featured image',
                ready: state.featuredMedia > 0,
                detail:
                    state.featuredMedia > 0
                        ? 'Selected'
                        : 'Required before publication',
            },
            {
                key: 'displayed-author',
                label: 'Displayed author',
                ready:
                    normalizeText(
                        meta.revelations_author
                    ).trim() !== '',
                detail:
                    normalizeText(
                        meta.revelations_author
                    ).trim() !== ''
                        ? 'Complete'
                        : 'Required before publication',
            },
            {
                key: 'excerpt',
                label: 'Excerpt',
                ready:
                    normalizeText(
                        state.excerpt
                    ).trim() !== '',
                detail:
                    normalizeText(
                        state.excerpt
                    ).trim() !== ''
                        ? 'Complete'
                        : 'Required before publication',
            },
            {
                key: 'category',
                label: 'Category',
                ready:
                    normalizeIds(
                        state.categories
                    ).length === 1,
                detail:
                    normalizeIds(state.categories).length === 1
                        ? 'Selected'
                        : 'Select exactly one editorial category',
            },
            {
                key: 'seo-title',
                label: 'SEO title',
                ready:
                    normalizeText(
                        meta.revelations_seo_title
                    ).trim() !== '',
                detail:
                    normalizeText(
                        meta.revelations_seo_title
                    ).trim() !== ''
                        ? 'Complete'
                        : 'Required before publication',
            },
            {
                key: 'seo-description',
                label: 'SEO description',
                ready:
                    normalizeText(
                        meta.revelations_seo_description
                    ).trim() !== '',
                detail:
                    normalizeText(
                        meta.revelations_seo_description
                    ).trim() !== ''
                        ? 'Complete'
                        : 'Required before publication',
            },
            {
                key: 'editorial-review',
                label: 'Editorial review',
                ready: reviewReady,
                detail: reviewDetail,
            },
        ];

        const readyCount =
            readinessItems.filter(
                function (item) {
                    return item.ready;
                }
            ).length;

        const allReady =
            readyCount ===
            readinessItems.length;

        return el(
            PluginDocumentSettingPanel,
            {
                name: 'revelations-article-fields',
                title: 'REVELATIONS fields',
                icon: 'admin-post',
                initialOpen: true,
            },

            el(
                Fragment,
                null,

                el(
                    'div',
                    {
                        style: {
                            marginBottom: '20px',
                        },
                    },

                    el(
                        'h3',
                        {
                            style: {
                                margin:
                                    '0 0 10px',
                                fontSize: '13px',
                            },
                        },
                        'Publication readiness'
                    ),

                    el(
                        components.Notice,
                        {
                            status: allReady
                                ? 'success'
                                : 'warning',
                            isDismissible: false,
                        },
                        allReady
                            ? 'Saved version is ready to publish'
                            : (
                                readyCount +
                                ' of ' +
                                readinessItems.length +
                                ' publication requirements complete.'
                            )
                    ),

                    el(
                        'div',
                        {
                            style: {
                                marginTop: '8px',
                            },
                        },
                        readinessItems.map(
                            statusRow
                        )
                    )
                ),

                el(components.TextControl, {
                    label: 'Displayed author',
                    help:
                        'Author name displayed on the REVELATIONS website.',
                    value:
                        meta.revelations_author ||
                        '',
                    onChange: function (value) {
                        updateMeta(
                            'revelations_author',
                            value
                        );
                    },
                }),

                el(components.SelectControl, {
                    label: 'Editorial category',
                    help: 'Select exactly one editorial category.',
                    value: String(normalizeIds(state.categories)[0] || ''),
                    options: [{ label: 'Select a category', value: '' }].concat(
                        (editorData.editorialCategories || []).map(
                            function (term) {
                                return { label: term.name, value: String(term.id) };
                            }
                        )
                    ),
                    onChange: function (value) {
                        editor.editPost({ categories: value ? [Number(value)] : [] });
                    },
                }),

                el(components.TextControl, {
                    label: 'SEO title',
                    value:
                        meta.revelations_seo_title ||
                        '',
                    onChange: function (value) {
                        updateMeta(
                            'revelations_seo_title',
                            value
                        );
                    },
                }),

                el(components.TextareaControl, {
                    label: 'SEO description',
                    rows: 4,
                    value:
                        meta.revelations_seo_description ||
                        '',
                    onChange: function (value) {
                        updateMeta(
                            'revelations_seo_description',
                            value
                        );
                    },
                }),

                el('hr', { style: { margin: '24px 0 16px' } }),
                el('h3', { style: { margin: '0 0 6px', fontSize: '13px' } }, 'ARTICLE ENRICHMENT'),
                el('p', { style: { margin: '0 0 14px', color: '#646970', fontSize: '12px' } }, 'Optional public editorial context. Changes require a new editorial review.'),

                el(components.TextareaControl, {
                    label: 'THE REVELATION',
                    help: 'The central editorial takeaway. Plain text, 60-150 words recommended, 180 words maximum.',
                    rows: 5,
                    value: meta.revelations_revelation || '',
                    onChange: function (value) { updateMeta('revelations_revelation', value); },
                }),
                el('p', { style: { margin: '-8px 0 14px', fontSize: '12px', color: (normalizeText(meta.revelations_revelation).match(/[\\p{L}\\p{N}]+/gu) || []).length > 180 ? '#b32d2e' : '#646970' } },
                  (normalizeText(meta.revelations_revelation).match(/[\\p{L}\\p{N}]+/gu) || []).length + ' / 180 words'),
                el(components.TextareaControl, { label: 'SOURCE NOTE', help: 'Public context about the reporting or source basis.', rows: 3, value: meta.revelations_source_note || '', onChange: function (value) { updateMeta('revelations_source_note', value); } }),
                el(components.TextareaControl, { label: 'EDITORIAL NOTE', help: 'Public editorial or process context.', rows: 3, value: meta.revelations_editorial_note || '', onChange: function (value) { updateMeta('revelations_editorial_note', value); } }),
                el(components.TextareaControl, { label: 'DISCLOSURE', help: 'A material relationship, correction, or conflict readers should know about.', rows: 3, value: meta.revelations_disclosure || '', onChange: function (value) { updateMeta('revelations_disclosure', value); } }),
                el('div', { style: { marginBottom: '16px' } },
                    el('strong', null, 'PUBLIC SOURCES'),
                    el('p', { style: { margin: '4px 0 8px', color: '#646970', fontSize: '12px' } }, 'Ordered, editor-approved public sources only.'),
                    publicSources(meta.revelations_public_sources).map(function (source, index) {
                        const sources = publicSources(meta.revelations_public_sources);
                        const save = function (next) { updateMeta('revelations_public_sources', JSON.stringify(next)); };
                        return el('div', { key: index, style: { borderTop: '1px solid #e0e0e0', paddingTop: '8px' } },
                            el(components.TextControl, { label: 'Label', value: source.label, onChange: function (value) { sources[index].label = value; save(sources); } }),
                            el(components.TextControl, { label: 'URL', type: 'url', value: source.url, onChange: function (value) { sources[index].url = value; save(sources); } }),
                            el('div', { style: { display: 'flex', gap: '6px', marginBottom: '8px' } },
                                el(components.Button, { isSecondary: true, isSmall: true, disabled: index === 0, onClick: function () { const next = sources.slice(); [next[index - 1], next[index]] = [next[index], next[index - 1]]; save(next); } }, 'Move up'),
                                el(components.Button, { isSecondary: true, isSmall: true, disabled: index === sources.length - 1, onClick: function () { const next = sources.slice(); [next[index], next[index + 1]] = [next[index + 1], next[index]]; save(next); } }, 'Move down'),
                                el(components.Button, { isDestructive: true, isSmall: true, onClick: function () { save(sources.filter(function (_, itemIndex) { return itemIndex !== index; })); } }, 'Remove')
                            )
                        );
                    }),
                    el(components.Button, { isSecondary: true, isSmall: true, onClick: function () { const sources = publicSources(meta.revelations_public_sources); sources.push({ label: '', url: '' }); updateMeta('revelations_public_sources', JSON.stringify(sources)); } }, 'Add source')
                ),

                el(components.TextControl, {
                    label: 'YouTube URL',
                    type: 'url',
                    value:
                        meta.revelations_youtube_url ||
                        '',
                    onChange: function (value) {
                        updateMeta(
                            'revelations_youtube_url',
                            value
                        );
                    },
                }),

                el(components.ToggleControl, {
                    label: 'Featured article',
                    checked: Boolean(
                        meta.revelations_featured
                    ),
                    onChange: function (value) {
                        updateMeta(
                            'revelations_featured',
                            value
                        );
                    },
                }),

                el(components.ToggleControl, {
                    label: 'Gated article',
                    help:
                        'Restrict access to the full article.',
                    checked: Boolean(
                        meta.revelations_is_gated
                    ),
                    onChange: function (value) {
                        updateMeta(
                            'revelations_is_gated',
                            value
                        );
                    },
                }),

                el(components.TextControl, {
                    label: 'Legacy article ID',
                    help:
                        'Original ID from the previous platform.',
                    value:
                        meta.revelations_legacy_id ||
                        '',
                    onChange: function (value) {
                        updateMeta(
                            'revelations_legacy_id',
                            value
                        );
                    },
                })
            )
        );
    }

    registerPlugin(
        'revelations-cms-fields',
        {
            render:
                RevelationsArticlePanel,
            icon: 'admin-post',
        }
    );
})(window.wp);

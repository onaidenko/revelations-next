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
                    );
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
                    ).length > 0,
                detail:
                    normalizeIds(
                        state.categories
                    ).length > 0
                        ? 'Selected'
                        : 'Required before publication',
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
                            ? 'Ready to publish.'
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

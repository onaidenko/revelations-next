export const SECTIONS = {
  news: {
    title: 'News',
    subtitle: 'Sharp signals & insights',
    description:
      'Not news — meaning. Quick insights that cut through noise and reveal what actually matters.',
  },

  people: {
    title: 'People',
    subtitle: 'Founders, visionaries, investors',
    description:
      'No polished biographies. No theatre. Just sharp conversations about ambition, taste, pressure, timing, failure, influence — and the strange little decisions that shape tomorrow.',
    seoDescription:
      'Sharp conversations with founders, investors and creators about ambition, pressure, failure, influence and the decisions shaping tomorrow.',
  },

  tech: {
    title: 'Tech',
    subtitle: 'Deep analysis of transformations',
    description:
      'How AI rewires thinking. How money, work, and status are evolving. Clean, analytical, precise.',
  },

  places: {
    title: 'Places',
    subtitle: 'Spaces where the future already exists',
    description:
      'Hotels, villas, private clubs, curated events. Places where tech is embedded so deeply you stop noticing it.',
  },

  unspoken: {
    title: 'Unspoken',
    subtitle: 'Hidden truths and uncomfortable realities',
    description:
      'Founder failures. Internal conflicts. Toxic dynamics. The backstage of PR and investments.',
  },

  podcast: {
    title: 'Podcast',
    subtitle: 'Conversations that reveal',
    description:
      'Not lectures — revelations. Tech discussed through experience, not explanation.',
  },
};


export function isEditorialSection(value) {
  return (
    typeof value === 'string' &&
    Object.prototype.hasOwnProperty.call(
      SECTIONS,
      value
    )
  );
}

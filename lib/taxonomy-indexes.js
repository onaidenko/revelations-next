export const TAXONOMY_INDEX_ORDER = [
  'topics',
  'series',
  'locations',
  'tags',
];

const INDEXES = {
  topics: {
    type: 'topics',
    name: 'Topics',
    singular: 'Topic',
    collectionSingular: 'topic',
    collectionPlural: 'topics',
    pathname: '/topics',
    description:
      'Explore REVELATIONS through the themes connecting its stories, from AI and robotics to founders, health, digital money, creative industries, and future places.',
    archiveDescription:
      'Browse the central themes connecting REVELATIONS stories.',
    emptyMessage: 'Topics are being curated',
    actionLabel: 'Explore topic',
  },
  series: {
    type: 'series',
    name: 'Series',
    singular: 'Series',
    collectionSingular: 'series',
    collectionPlural: 'series',
    pathname: '/series',
    description:
      'Follow recurring REVELATIONS editorial series that bring connected interviews, reports, and perspectives together in one place.',
    archiveDescription:
      'Follow recurring editorial formats and connected story arcs.',
    emptyMessage: 'Series are being curated',
    actionLabel: 'Explore series',
  },
  locations: {
    type: 'locations',
    name: 'Locations',
    singular: 'Location',
    collectionSingular: 'location',
    collectionPlural: 'locations',
    pathname: '/locations',
    description:
      'Explore the cities, countries, venues, and innovation hubs shaping the people, technologies, and ideas covered by REVELATIONS.',
    archiveDescription:
      'Explore the places and innovation hubs behind the stories.',
    emptyMessage: 'Locations are being curated',
    actionLabel: 'Explore location',
  },
  tags: {
    type: 'tags',
    name: 'Entities',
    singular: 'Entity',
    collectionSingular: 'entity',
    collectionPlural: 'entities',
    pathname: '/tags',
    description:
      'Discover the people, companies, brands, products, initiatives, and venues that connect REVELATIONS coverage across industries and regions.',
    archiveDescription:
      'Discover the named people, organizations, brands, and initiatives.',
    emptyMessage: 'Entities are being curated',
    actionLabel: 'Explore entity',
  },
};

export function getTaxonomyIndexConfig(type) {
  const config = INDEXES[type];

  if (!config) {
    throw new Error(`Unsupported taxonomy index: ${type}`);
  }

  return config;
}

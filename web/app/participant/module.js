define( {
  subject: 'participant',
  name: {
    singular: 'participant',
    plural: 'participants',
    possessive: 'participant\'s',
    pluralPossessive: 'participants\''
  },
  inputList: {
    active: {
      title: 'Active',
      type: 'boolean'
    },
    uid: {
      title: 'Unique ID',
      type: 'string',
      constant: true
    },
    source: {
      column: 'source.name',
      title: 'Source',
      type: 'string',
      constant: true
    },
    cohort: {
      column: 'cohort.name',
      title: 'Cohort',
      type: 'string',
      constant: true
    },
    first_name: {
      title: 'First Name',
      type: 'string'
    },
    other_name: {
      title: 'Other/Nickname',
      type: 'string'
    },
    last_name: {
      title: 'Last Name',
      type: 'string'
    },
    language_id: {
      title: 'Preferred Language',
      type: 'enum'
    },
    default_site: {
      column: 'default_site.name',
      title: 'Default Site',
      type: 'string',
      constant: true
    },
    preferred_site_id: {
      column: 'preferred_site.id',
      title: 'Preferred Site',
      type: 'enum'
    },
    email: {
      title: 'Email',
      type: 'string',
      help: 'Must be in the format "account@domain.name"'
    },
    mass_email: {
      title: 'Mass Emails',
      type: 'boolean',
      help: 'Whether the participant wishes to be included in mass emails such as newsletters, ' +
            'holiday greetings, etc.'
    },
    gender: {
      title: 'Sex',
      type: 'enum'
    },
    date_of_birth: {
      title: 'Date of Birth',
      type: 'date'
    },
    age_group_id: {
      title: 'Age Group',
      type: 'enum'
    },
    state_id: {
      title: 'Final State',
      type: 'enum'
    },
    withdraw_option: {
      title: 'Withdraw Option',
      type: 'string',
      constant: true
    }
  },
  columnList: {
    uid: {
      column: 'participant.uid',
      title: 'UID'
    },
    first: {
      column: 'participant.first_name',
      title: 'First'
    },
    last: {
      column: 'participant.last_name',
      title: 'Last'
    },
    active: {
      column: 'participant.active',
      title: 'Active',
      filter: 'cnYesNo'
    },
    source: {
      column: 'source.name',
      title: 'Source'
    },
    cohort: {
      column: 'cohort.name',
      title: 'Cohort'
    },
    site: {
      column: 'site.name',
      title: 'Site'
    }
  },
  defaultOrder: {
    column: 'uid',
    reverse: false
  }
} );

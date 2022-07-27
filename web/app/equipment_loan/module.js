cenozoApp.defineModule({
  name: "equipment_loan",
  models: ["add", "list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: [{
          subject: "participant",
          column: "participant.uid",
        }, {
          subject: "equipment",
          column: "equipment.serial_number",
        }],
      },
      name: {
        singular: "equipment loan",
        plural: "equipment loans",
        possessive: "equipment loan's",
      },
      columnList: {
        uid: {
          column: "participant.uid",
          title: "Participant",
        },
        equipment_type: {
          column: "equipment_type.name",
          title: "Equipment Type",
        },
        serial_number: {
          column: "equipment.serial_number",
          title: "Serial Number",
        },
        start_datetime: {
          title: "Loan Date & Time",
          type: "datetime",
        },
        end_datetime: {
          title: "Return Date & Time",
          type: "datetime",
        },
      },
      defaultOrder: {
        column: "start_datetime",
        reverse: true,
      },
    });

    module.addInputGroup("", {
      participant_id: {
        title: "Participant",
        type: "lookup-typeahead",
        typeahead: {
          table: "participant",
          select:
            'CONCAT( participant.first_name, " ", participant.last_name, " (", uid, ")" )',
          where: ["participant.first_name", "participant.last_name", "uid"],
        },
        isConstant: "view",
      },
      equipment_id: {
        title: "Serial Number",
        type: "lookup-typeahead",
        typeahead: {
          table: "equipment",
          select: 'CONCAT( equipment_type.name, ": ", equipment.serial_number )',
          where: "equipment.serial_number",
        },
        help: "Type in the serial number of the device (do not include the device type",
      },
      start_datetime: {
        title: "Loan Date & Time",
        type: "datetime",
        max: "now",
      },
      end_datetime: {
        title: "Return Date & Time",
        type: "datetime",
        max: "now",
        isExcluded: "add",
      },
      note: {
        title: "Note",
        type: "text",
      },
    });
  },
});

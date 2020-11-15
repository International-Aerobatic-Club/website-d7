require 'mysql2'
require 'csv'

full_course_names = {
  'tailwheel' => 'Tailwheel endorsement',
  'spin' => 'Stall/spin recovery',
  'upset' => 'Unusual attitude/upset training',
  'basicAerobatics' => 'Basic aerobatics',
  'advancedAerobatics' => 'Competition aerobatics',
  'pitts' => 'Pitts Checkout',
  'RVaerobatics' => 'RV aerobatics',
  'glider' => 'Glider aerobatics',
}

schools = []
instructors = []

client = Mysql2::Client.new(host: "localhost", username: "root", password: 'zWG84Ff5n8TF', database: 'iacusn')

results = client.query('select * from instructor')
# {"id"=>326, "schoolID"=>176, "givenName"=>"Sean", "sirName"=>"VanHatten", "cert"=>"CFI-A", "isACE"=>"n"}

results.each do |row|
  instructors << {
    school_id: row['schoolID'],
    name: row['givenName'] + ' ' + row['sirName'],
    certs: [ (row['cert'] == 'none' ? nil : row['cert']), (row['isACE'] == 'y' ? 'ACE' : nil) ].compact.join(', '),
  }
end


results = client.query('select * from school')

CSV(STDOUT) do |csv|

  # Output a header row
  csv << results.first.keys.find_all{ |v| v != 'airCity' && v != 'airState' } + [ 'instructors' ]

  # Massage and then output each school
  results.each do |school|
    instrs = instructors.find_all{ |i| i[:school_id] == school['id'] }
    school['instructors'] = instrs.map{ |h| [ h[:name], (h[:certs].empty? ? nil : h[:certs]) ].compact.join(': ') }.join(', ')
    school['course'] = school['course'].split(',').map{ |c| full_course_names[c] }.join(', ')
    if school['airCity'] || school['airState']
      school['airport'] ||= ''
      school['airport'] += ' -- ' + [ school['airCity'], school['airState'] ].join(', ')
    end
    school.delete('airCity')
    school.delete('airState')
    school['country'] = 'USA' if school['country'].nil? || school['country'].length == 0
    csv << school.values
  end
end

require 'typhoeus'
require 'yaml'
require 'json'

Secrets = YAML.load_file(File.join(File.dirname(__FILE__), 'mailgun_secrets.yml'))

url = "#{Secrets['mailgun_url']}/#{Secrets['domain']}/bounces"

loop do

  response = Typhoeus.get(url, userpwd: Secrets['api_key'])

  headers = response.response_headers.split
  unless headers[1] == "200"
    puts "Request failed, response code = #{headers[1]} #{headers[2]}"
    exit 1
  end

  body = JSON.parse(response.response_body)
  break if body['items'].empty?

  puts body['items'].map{ |i| i['address'] }.join("\n")

  url = body['paging']['next']

end

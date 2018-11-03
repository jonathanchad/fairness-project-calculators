# Fairness Project Calculators

This repo contains 2 plugins. One is for the calculators themselves and the other is Advanced Custom Fields. The calculators use ACF for the fields associated with each.

# Development Environment
Install docker and docker compose.

Add the environmental variables listed in the .env.example to a .env file.

Then run:
`docker-compose up`

# Deployment to Production
Move the two plugins into the plugins directory of your production install of WordPress.

# Creating Medicaid States and Min Wage States
Both post types appear in the admin side bar. If the permalink looks incorrect after you make one make sure the permalink structure includes post_name.

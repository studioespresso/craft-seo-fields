action "deploy docs" {
  uses = "./.deploy-docs/"
}

workflow "New workflow" {
  on = "push"
  resolves = ["Deploy docs!"]
}

action "Deploy docs!" {
  uses = "./.deploy-docs"
}

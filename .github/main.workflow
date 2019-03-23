workflow "Docs to github pages" {
  on = "push"
  resolves = ["Deploy docs!"]
}

action "Deploy docs!" {
  uses = "./.deploy-docs"
}

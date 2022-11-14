# Overview of Node components.

## Texts:
A textnode is an **immutable node**. It cannot be modified once inserted into the database. These nodes are at the core of the entitylinking system.

Texts can be made **public** or **private**. A public text can be edited by anyone with an account; a private text can only be annotated by the researcher who is considered the text-owner. A text can only be owned by one person at the same time.

## Annotations:
Annotations are text snippets which are highlighted, the annotation-node itself stores the start and end-position of an annotation, the user who created the annotation and is the link between a **text** and an **entity**. Inside the annotation-node there's no information on what the highlight is referring to. This referral is stored in the edge which links the annotation with an entity.

## Entity:
Multiple entities can be stored in the database. The focal points of this tool are **people** and **places**. The entitymodel is flexible, meaning it can be extended by other entities fitting the project scope. This can be done by adding new components to the configuration file.

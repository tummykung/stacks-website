CREATE TABLE "comments" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL UNIQUE , "tag" VARCHAR NOT NULL, "author" VARCHAR, "site" VARCHAR, "date" DATETIME DEFAULT CURRENT_TIMESTAMP, "comment" TEXT, "email" VARCHAR)
CREATE TABLE "sections" ("number" VARCHAR PRIMARY KEY  NOT NULL ,"title" VARCHAR,"filename" VARCHAR NOT NULL )
CREATE TABLE "tags" ("tag" VARCHAR PRIMARY KEY  NOT NULL ,"label" VARCHAR,"file" VARCHAR,"chapter_page" INTEGER,"book_page" INTEGER,"type" VARCHAR,"book_id" VARCHAR,"value" TEXT, "active" BOOL NOT NULL  DEFAULT TRUE, "name" VARCHAR, "position" INTEGER)
CREATE TABLE "bibliography_items" ("name" VARCHAR PRIMARY KEY NOT NULL UNIQUE, "type" NOT NULL)
CREATE TABLE "bibliography_values" ("name" VARCHAR NOT NULL, "key" VARCHAR NOT NULL, "value" VARCHAR NOT NULL)
CREATE TABLE "macros" ("name" VARCHAR NOT NULL, "value" VARCHAR NOT NULL)
CREATE VIRTUAL TABLE "tags_search" USING fts3(tag, text, text_without_proofs)
CREATE INDEX "position" ON "tags" ("position")

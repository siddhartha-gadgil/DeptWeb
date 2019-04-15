#!/usr/bin/env amm

import $ivy.`com.typesafe.akka::akka-http:10.1.0-RC2`
import $ivy.`com.typesafe.akka::akka-stream:2.5.9`
import akka.actor.ActorSystem
import akka.http.scaladsl.Http
import akka.http.scaladsl.model._
import akka.http.scaladsl.server.Directives._
import akka.stream.ActorMaterializer
import scala.io.StdIn
import ammonite.ops._
import akka.stream.scaladsl.FileIO
import akka.http.scaladsl.model.headers._

import scala.concurrent._
import akka.util.ByteString
import akka.http.scaladsl.model.{HttpResponse, MediaTypes,HttpEntity}

import java.nio._

implicit val system = ActorSystem("my-system")
implicit val materializer = ActorMaterializer()
// needed for the future flatMap/onComplete in the end
implicit val executionContext = system.dispatcher

val route = path("dept.tgz") {
  withRequestTimeout{
  val file = "tarball/dept.tgz"
  val f = Paths.get(file)
  println(f.length)
  val responseEntity = HttpEntity.Chunked(
    MediaTypes.`application/octet-stream`,
    FileIO.fromPath(f,1 * 1024* 1024)
  )

  complete(HttpResponse(200, entity = responseEntity)
    .withHeaders(RawHeader("Content-Disposition", s"attachment; filename=$file")))
    }
  }


val bindingFuture = Http().bindAndHandle(route, "localhost", 6060)

println(s"Server online at http://localhost:6060/\nPress RETURN to stop...")
StdIn.readLine() // let it run until user presses return
bindingFuture
  .flatMap(_.unbind()) // trigger unbinding from the port
  .onComplete(_ => system.terminate()) // and shutdown when done
